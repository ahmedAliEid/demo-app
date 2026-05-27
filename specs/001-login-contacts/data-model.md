# Data Model: Login & Contact Address Management

**Feature**: 001-login-contacts | **Date**: 2026-05-22

## Entities

### User

Represents an authenticated system user. Users are seeded by an admin; no
self-registration in Phase 1.

| Field | Type | Constraints | Notes |
|-------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | Auto-generated |
| `username` | `VARCHAR(100)` | NOT NULL, UNIQUE | Login identifier |
| `password_hash` | `VARCHAR(255)` | NOT NULL | BCrypt hash; never stored in plain text |
| `role` | `VARCHAR(20)` | NOT NULL, DEFAULT `'user'` | `'admin'` or `'user'` |
| `created_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT NOW() | |
| `updated_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT NOW() | Updated by trigger or service layer |

**Validation rules**:
- `username`: 3–100 characters, alphanumeric + underscore/hyphen only
- `role`: MUST be one of `admin`, `user`; other values rejected

**Java domain class**: `User implements UserDetails`

---

### Contact

Represents the person or organisation associated with an address.

| Field | Type | Constraints | Notes |
|-------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | Auto-generated |
| `full_name` | `VARCHAR(255)` | NOT NULL | Display name of the contact |
| `owner_id` | `BIGINT` | NOT NULL, FK → `users(id)` | The user who created this contact |
| `created_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT NOW() | |
| `updated_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT NOW() | |

**Constraints**:
- `UNIQUE(owner_id, full_name)` — DB-level enforcement of per-owner name uniqueness
- Admin-scope uniqueness (across all contacts) enforced at service layer before insert

**Validation rules**:
- `full_name`: 1–255 characters, trimmed; blank is rejected
- `owner_id`: Set automatically to the authenticated user's ID on creation; not
  user-provided in the request body

**Java domain class**: `Contact`

---

### Address

The postal/contact address belonging to a Contact. One-to-one relationship; Address
cannot exist without its parent Contact.

| Field | Type | Constraints | Notes |
|-------|------|-------------|-------|
| `id` | `BIGSERIAL` | PK | Auto-generated |
| `contact_id` | `BIGINT` | NOT NULL, UNIQUE, FK → `contacts(id) ON DELETE CASCADE` | One address per contact |
| `street_line1` | `VARCHAR(255)` | NOT NULL | |
| `street_line2` | `VARCHAR(255)` | nullable | Optional second address line |
| `city` | `VARCHAR(100)` | NOT NULL | Used in filter dropdown |
| `state_province` | `VARCHAR(100)` | nullable | State, province, or region |
| `postal_code` | `VARCHAR(20)` | nullable | Format varies by country |
| `country` | `VARCHAR(100)` | NOT NULL | Used in filter dropdown |
| `phone` | `VARCHAR(50)` | nullable | No format enforced; stored as-is |

**Validation rules**:
- `street_line1`: 1–255 characters, required
- `city`: 1–100 characters, required
- `country`: 1–100 characters, required
- `phone`: optional; if provided, max 50 characters

**Java domain class**: `Address`

---

## Relationships

```
User ─────────────── Contact
 1                    0..*
                (owner_id FK)

Contact ──────────── Address
    1                  1
                (contact_id FK, UNIQUE)
```

- A `User` owns zero or more `Contact` records.
- Each `Contact` has exactly one `Address` (created together, updated together).
- Deleting a `Contact` cascades to delete its `Address`.

---

## Database Schema (Flyway Migrations)

### V1__create_users.sql

```sql
CREATE TABLE users (
    id           BIGSERIAL    PRIMARY KEY,
    username     VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role         VARCHAR(20)  NOT NULL DEFAULT 'user'
                              CHECK (role IN ('admin', 'user')),
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
```

### V2__create_contacts.sql

```sql
CREATE TABLE contacts (
    id         BIGSERIAL    PRIMARY KEY,
    full_name  VARCHAR(255) NOT NULL,
    owner_id   BIGINT       NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_contact_name_per_owner UNIQUE (owner_id, full_name)
);

CREATE INDEX idx_contacts_owner ON contacts(owner_id);
CREATE INDEX idx_contacts_full_name ON contacts(full_name);
```

### V3__create_addresses.sql

```sql
CREATE TABLE addresses (
    id             BIGSERIAL    PRIMARY KEY,
    contact_id     BIGINT       NOT NULL UNIQUE
                                REFERENCES contacts(id) ON DELETE CASCADE,
    street_line1   VARCHAR(255) NOT NULL,
    street_line2   VARCHAR(255),
    city           VARCHAR(100) NOT NULL,
    state_province VARCHAR(100),
    postal_code    VARCHAR(20),
    country        VARCHAR(100) NOT NULL,
    phone          VARCHAR(50)
);

CREATE INDEX idx_addresses_city    ON addresses(city);
CREATE INDEX idx_addresses_country ON addresses(country);
```

---

## State Transitions

Contacts have no complex lifecycle state in Phase 1. The only state changes are:

| Event | Trigger | Result |
|-------|---------|--------|
| Created | `POST /api/v1/contacts` | Contact + Address rows inserted |
| Updated | `PUT /api/v1/contacts/{id}` | `full_name` and/or Address fields updated; `updated_at` refreshed |
| (Future) Deleted | Phase 2+ | Soft-delete or hard-delete TBD |

---

## DTO Mapping

| Domain | DTO (Request) | DTO (Response) |
|--------|---------------|----------------|
| `Contact` + `Address` | `ContactRequest` | `ContactResponse` (detail), `ContactSummary` (list row) |
| `User` | `LoginRequest` | `LoginResponse` (JWT token) |

**`ContactSummary`** (list view): `id`, `fullName`, `city`, `country`

**`ContactResponse`** (detail/edit view): `id`, `fullName`, `streetLine1`, `streetLine2`,
`city`, `stateProvince`, `postalCode`, `country`, `phone`, `createdAt`, `updatedAt`

**`ContactRequest`** (create/update): `fullName`, `streetLine1`, `streetLine2` (opt),
`city`, `stateProvince` (opt), `postalCode` (opt), `country`, `phone` (opt)

JPA entities are **never** serialised directly to HTTP responses; all API payloads use DTOs.
