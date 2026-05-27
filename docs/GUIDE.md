# Demo Contacts App — Operator & User Guide

**Version**: 2.0 | **Last Updated**: 2026-05-24 | **Change**: WordPress UI replaced by Laravel PHP (ADR-008)

---

## System Overview

The Demo Contacts App is a two-layer internal web application for managing contact addresses with role-based access control.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           USER'S BROWSER                                │
│                      http://localhost:8888                              │
└────────────────────────────┬────────────────────────────────────────────┘
                             │  HTTP (port 8888)
                             ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        NGINX REVERSE PROXY                              │
│                         (port 8888 → 9000)                              │
└────────────────────────────┬────────────────────────────────────────────┘
                             │  FastCGI (port 9000)
                             ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     LARAVEL PHP FRONTEND                                │
│                  (PHP 8.2 · Laravel 11 · PHP-FPM)                       │
│                                                                         │
│   ┌──────────────────────┐    ┌──────────────────────────────────────┐  │
│   │  routes/web.php      │    │    app/Http/Controllers/             │  │
│   │  ├─ GET  /login      │    │    ├─ AuthController.php             │  │
│   │  ├─ POST /login      │    │    ├─ ContactController.php          │  │
│   │  ├─ POST /logout     │    │    └─ AddressController.php          │  │
│   │  ├─ GET  /contacts   │    └──────────────────────────────────────┘  │
│   │  ├─ GET  /contacts/  │    ┌──────────────────────────────────────┐  │
│   │  │       {id}        │    │    app/Services/                     │  │
│   │  └─ ...              │    │    └─ ApiClient.php (Http facade)    │  │
│   └──────────────────────┘    └──────────────┬───────────────────────┘  │
│                                              │                          │
│   resources/views/ (Blade)                   │                          │
│   ├─ auth/login.blade.php                    │                          │
│   ├─ contacts/index.blade.php                │                          │
│   ├─ contacts/show.blade.php                 │                          │
│   └─ contacts/form.blade.php                 │                          │
└─────────────────────────────────────────────────────────────────────────┘
                                               │  HTTP REST (port 8080)
                                               │  Bearer JWT
                                               ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    SPRING BOOT REST API                                 │
│                  (Java 21 · Spring Boot 3.3)                            │
│                                                                         │
│   ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────┐ │
│   │  AuthController  │  │ ContactController│  │  SecurityConfig      │ │
│   │  POST /auth/login│  │  GET  /contacts  │  │  JWT filter chain    │ │
│   │  POST /auth/logout│ │  POST /contacts  │  │  Role: ADMIN / USER  │ │
│   └────────┬─────────┘  │  GET  /contacts/ │  └──────────────────────┘ │
│            │            │  PUT  /contacts/ │                            │
│            │            │  GET  /filter-   │                            │
│            │            │       options    │                            │
│            │            └────────┬─────────┘                            │
│   ┌────────▼─────────────────────▼──────────────────────────────────┐  │
│   │              Service Layer + JPA Repository                      │  │
│   │              Flyway DB Migrations (V1–V3)                        │  │
│   └──────────────────────────────┬──────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                   │  JDBC (port 5432)
                                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    POSTGRESQL 15 DATABASE                               │
│                    DB: demo_contacts                                    │
│                    Tables: users · contacts · addresses                 │
└─────────────────────────────────────────────────────────────────────────┘

         ── Phase 2 (future) ──────────────────────────────────────────
              Spring Boot → Kafka → Consumer Services
         ─────────────────────────────────────────────────────────────
```

---

## Service URLs

| Service              | URL                                     | Notes                          |
|----------------------|-----------------------------------------|--------------------------------|
| Laravel frontend     | `http://localhost:8888`                 | Main UI entry point            |
| Spring Boot API      | `http://localhost:8080`                 | REST API base                  |
| API docs (Swagger)   | `http://localhost:8080/api/docs`        | Interactive API explorer       |
| API health check     | `http://localhost:8080/actuator/health` | Returns `{"status":"UP"}`      |
| PHP health check     | `http://localhost:8888/health`          | Returns HTTP 200               |
| Metrics (Prometheus) | `http://localhost:8080/actuator/prometheus` | Scrape endpoint           |

---

## Quick Start (Docker Compose — Recommended)

The fastest way to run the full stack locally.

```bash
# 1. Clone the repo and switch to the feature branch
git clone https://github.com/ahmedAliEid/demo-app.git
cd demo-app
git checkout 002-php-frontend-migration

# 2. Copy and configure environment variables
cp .env.example .env           # edit DB_PASSWORD, JWT_SECRET, and APP_KEY before use

# 3. Start all services (PostgreSQL · Spring Boot · PHP-FPM · Nginx)
docker compose up -d

# 4. Wait for health checks (≈ 60 s on first run)
docker compose ps              # all services should show "healthy"

# 5. Open the app
open http://localhost:8888
```

> **JWT_SECRET** must be at least 32 characters.
> **APP_KEY** must be a valid Laravel key: run `php artisan key:generate` or set `base64:<random-32-bytes>`.
> Never commit `.env` to git.

---

## Environment Variables

### Spring Boot API

| Variable               | Required | Default                   | Description                                     |
|------------------------|----------|---------------------------|-------------------------------------------------|
| `DB_URL`               | Yes      | —                         | JDBC URL: `jdbc:postgresql://postgres:5432/demo_contacts` |
| `DB_USERNAME`          | Yes      | `demo`                    | PostgreSQL username                             |
| `DB_PASSWORD`          | Yes      | `demo_local`              | PostgreSQL password                             |
| `JWT_SECRET`           | Yes      | `demo-local-…`            | HMAC-SHA256 signing key, min 32 chars           |
| `JWT_EXPIRY_SECONDS`   | No       | `1800`                    | Session lifetime in seconds (30 min)            |
| `SPRING_PROFILES_ACTIVE` | No     | —                         | Set to `test` to use H2 in-memory DB            |

### Laravel PHP Frontend

| Variable       | Required | Default                     | Description                                          |
|----------------|----------|-----------------------------|------------------------------------------------------|
| `APP_KEY`      | Yes      | —                           | Laravel encryption key (`base64:<32-byte-string>`)   |
| `APP_URL`      | Yes      | `http://localhost:8888`     | Public URL of the frontend                           |
| `APP_ENV`      | No       | `local`                     | Environment: `local`, `staging`, `production`        |
| `BACKEND_URL`  | Yes      | `http://backend:8080`       | Internal Docker URL of the Spring Boot API           |
| `SESSION_LIFETIME` | No   | `30`                        | Session timeout in minutes                           |
| `LOG_CHANNEL`  | No       | `stack`                     | Laravel log channel; use `stderr` in Docker          |

---

## API Reference

All API endpoints are under the base path `/api/v1/`. Protected endpoints require an `Authorization: Bearer <token>` header.

### Authentication

#### Login
```
POST http://localhost:8080/api/v1/auth/login
Content-Type: application/json

{
  "username": "jsmith",
  "password": "s3cr3t!"
}
```
**Response 200:**
```json
{
  "token": "<JWT>",
  "expiresIn": 1800,
  "role": "admin"
}
```
**Error 401:** Invalid credentials — `{"status":401,"detail":"Invalid username or password"}`

#### Logout
```
POST http://localhost:8080/api/v1/auth/logout
Authorization: Bearer <token>
```
**Response 204** — Token is discarded server-side; Laravel session is cleared.

---

### Contacts

All contact endpoints require authentication. Access scope depends on role:
- **admin** — sees and manages all contacts across all users
- **user** — sees and manages only their own contacts

#### List contacts (paginated)
```
GET http://localhost:8080/api/v1/contacts
Authorization: Bearer <token>

# Optional query parameters:
?name=smith           # substring match on full name (case-insensitive)
?city=London          # exact city filter
?country=UK           # exact country filter
?page=0&size=20       # pagination (default: page 0, 20 per page)
```

#### Create a contact
```
POST http://localhost:8080/api/v1/contacts
Authorization: Bearer <token>
Content-Type: application/json

{
  "fullName": "Jane Smith",
  "streetLine1": "123 High Street",
  "streetLine2": "Apt 4B",
  "city": "London",
  "stateProvince": "England",
  "postalCode": "EC1A 1BB",
  "country": "UK",
  "phone": "+44 20 7946 0958"
}
```
**Response 201** — includes `Location` header and full contact object.
**Error 409** — duplicate name within your visible scope.

#### Get contact detail
```
GET http://localhost:8080/api/v1/contacts/{id}
Authorization: Bearer <token>
```

#### Update a contact
```
PUT http://localhost:8080/api/v1/contacts/{id}
Authorization: Bearer <token>
Content-Type: application/json

{ ... same body as create ... }
```
Users can only update their own contacts. Admins can update any.

#### Filter options (for dropdowns)
```
GET http://localhost:8080/api/v1/contacts/filter-options
Authorization: Bearer <token>
```
**Response:**
```json
{
  "cities": ["London", "Paris", "Berlin"],
  "countries": ["UK", "France", "Germany"]
}
```

---

## UI Walkthrough

### Login

1. Navigate to `http://localhost:8888`
2. Enter your username and password
3. On success, you are redirected to the contacts list
4. On failure, an error message is shown inline
5. The session expires after **30 minutes of inactivity** — you are automatically redirected back to login

### Contact List Page

- Displays contacts in a paginated table (20 per page)
- **Search** by name using the text box at the top
- **Filter** by city or country using the dropdown menus
- Click any row to view full contact details
- Click **Add Contact** to open the create form

### Add / Edit Contact

Required fields: Full Name, Street Line 1, City, Country.
All other fields (Street Line 2, State/Province, Postal Code, Phone) are optional.

- Duplicate names within your scope produce a conflict error
- Validation errors are shown next to each field

### Role Differences

| Action                            | User | Admin |
|-----------------------------------|------|-------|
| View own contacts                 | Yes  | Yes   |
| View all contacts                 | No   | Yes   |
| Edit own contacts                 | Yes  | Yes   |
| Edit others' contacts             | No   | Yes   |
| See all cities/countries in filters | No (own scope) | Yes (all) |

---

## User Management

There is no self-registration in Phase 1. Users must be seeded by an admin directly in the database.

```sql
-- Connect to PostgreSQL and run:
INSERT INTO users (username, password_hash, role)
VALUES (
  'jsmith',
  '$2a$12$<bcrypt-hash>',   -- generate with the command below
  'user'                    -- or 'admin'
);
```

To generate a bcrypt hash via Docker without installing extra tools:

```bash
docker run --rm httpd:alpine htpasswd -bnBC 12 "" yourpassword | tr -d ':\n'
```

---

## Running Tests

### Backend (Spring Boot)

```bash
cd backend

# Fast unit tests (H2 in-memory, no Docker needed)
./mvnw test

# Full integration tests (requires Docker for Testcontainers PostgreSQL)
./mvnw verify -Pintegration-tests

# Test coverage report (gate: 80% on service/ and domain/)
./mvnw verify jacoco:report
open target/site/jacoco/index.html
```

### PHP Frontend (Laravel)

```bash
cd frontend

# Install dependencies
composer install

# Run the full test suite (PHPUnit)
php artisan test

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage

# Run a specific test class
php artisan test --filter ContactControllerTest
```

---

## Manual Setup (Without Docker)

If you prefer to run services directly instead of via Docker Compose:

### 1. Start PostgreSQL

```bash
docker compose up -d postgres
```

### 2. Run Spring Boot

```bash
cd backend
export DB_URL=jdbc:postgresql://localhost:5432/demo_contacts
export DB_USERNAME=demo
export DB_PASSWORD=demo_local
export JWT_SECRET=change-me-in-production-must-be-at-least-32-chars
export JWT_EXPIRY_SECONDS=1800
./mvnw spring-boot:run
```

Flyway migrations run automatically on startup — no manual SQL needed.

### 3. Run Laravel PHP Frontend

```bash
cd frontend
composer install
cp .env.example .env
php artisan key:generate        # sets APP_KEY in .env

# Configure backend URL in .env:
# BACKEND_URL=http://localhost:8080

php artisan serve --port=8888
```

For production-like local setup with PHP-FPM + Nginx, use the Docker Compose stack.

---

## Observability

| Endpoint                          | Purpose                            |
|-----------------------------------|------------------------------------|
| `GET /actuator/health`            | Spring Boot liveness check         |
| `GET /actuator/prometheus`        | Prometheus metrics scrape endpoint |
| `GET /health`                     | Laravel PHP liveness check         |

All Spring Boot requests log a trace ID in structured JSON (SLF4J). Filter logs by `traceId` to trace a single request through the system. Laravel logs are written to `storage/logs/` (local) or `stderr` (Docker).

---

## Troubleshooting

| Symptom                             | Likely Cause                          | Fix                                                              |
|-------------------------------------|---------------------------------------|------------------------------------------------------------------|
| `401 Unauthorized` on API           | Token missing or expired              | Log in again to get a fresh token                                |
| `403 Forbidden` on contact          | User accessing another user's contact | Check the contact's owner; use admin account if needed           |
| `409 Conflict` on create            | Duplicate full name in your scope     | Use a different name                                             |
| Laravel shows `500` error           | `APP_KEY` not set                     | Run `php artisan key:generate` or set `APP_KEY` in `.env`        |
| Laravel shows `419` on form submit  | CSRF token mismatch                   | Clear browser cookies and reload; check session driver config    |
| Backend fails to start              | PostgreSQL not ready                  | Run `docker compose up -d postgres` and wait for `healthy` status |
| JWT_SECRET error on startup         | Secret too short                      | Set `JWT_SECRET` to a string of at least 32 characters           |
| Frontend can't reach backend        | `BACKEND_URL` misconfigured           | In Docker: use `http://backend:8080`; local: `http://localhost:8080` |

---

## Project Structure

```
demo-app/
├── docker-compose.yml              # Full local stack (Postgres · Spring Boot · PHP-FPM · Nginx)
├── backend/                        # Spring Boot (Java 21 / Maven)
│   ├── pom.xml
│   └── src/main/java/com/demo/app/
│       ├── controller/             # REST endpoints (Auth, Contact)
│       ├── service/                # Business logic
│       ├── security/               # JWT filter + UserDetailsService
│       ├── domain/                 # JPA entities (User, Contact, Address)
│       ├── dto/                    # Request/response objects
│       └── exception/              # Global error handler
├── frontend/                       # Laravel PHP (PHP 8.2 / Composer)
│   ├── composer.json
│   ├── artisan
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/        # AuthController, ContactController
│   │   │   ├── Middleware/         # Authenticate, RedirectIfAuthenticated
│   │   │   └── Requests/           # Form validation (Laravel Form Requests)
│   │   └── Services/
│   │       └── ApiClient.php       # Laravel Http facade wrapper for Spring Boot
│   ├── resources/
│   │   └── views/                  # Blade templates
│   │       ├── auth/login.blade.php
│   │       ├── contacts/index.blade.php
│   │       ├── contacts/show.blade.php
│   │       └── contacts/form.blade.php
│   └── routes/
│       └── web.php                 # All route definitions
├── specs/
│   ├── 001-login-contacts/
│   │   ├── contracts/openapi.yaml  # Authoritative API contract
│   │   ├── plan.md
│   │   └── data-model.md
│   └── 002-php-frontend-migration/
│       └── spec.md
└── docs/
    ├── GUIDE.md                    # This file
    └── adr/                        # Architecture Decision Records
        ├── ADR-001-technology-stack.md        # Partially superseded by ADR-008
        ├── ADR-002-layered-architecture.md
        ├── ADR-003-api-first-design.md
        ├── ADR-004-role-based-access-control.md
        ├── ADR-005-duplicate-contact-name-policy.md
        ├── ADR-006-session-timeout.md
        ├── ADR-007-contact-search-and-filter.md
        └── ADR-008-php-frontend-migration.md  # Supersedes ADR-001 WordPress decision
```
