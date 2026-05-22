# Demo Contacts App — Operator & User Guide

**Version**: 1.0 | **Feature**: Login & Contact Address Management | **Date**: 2026-05-22

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
│                     WORDPRESS FRONTEND                                  │
│                  (PHP 8.1 · WordPress 6.4)                              │
│                                                                         │
│   ┌──────────────────────┐    ┌──────────────────────────────────────┐  │
│   │  contacts-child theme│    │    demo-contacts-api plugin          │  │
│   │  ├─ login.html       │    │    ├─ class-api-client.php           │  │
│   │  ├─ contacts-list    │    │    ├─ class-auth-handler.php         │  │
│   │  ├─ contact-detail   │    │    └─ class-contact-handler.php      │  │
│   │  └─ contact-form     │    └──────────────┬───────────────────────┘  │
│   └──────────────────────┘                   │                          │
└──────────────────────────────────────────────┼──────────────────────────┘
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
│            │                     │                                      │
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

                         ┌───────────────────────────┐
                         │  MYSQL 8.0 DATABASE        │
                         │  DB: wordpress             │
                         │  (WordPress CMS data only) │
                         └───────────────────────────┘

         ── Phase 2 (future) ──────────────────────────────────────────
              Spring Boot → Kafka → Consumer Services
         ─────────────────────────────────────────────────────────────
```

---

## Service URLs

| Service | URL | Notes |
|---------|-----|-------|
| WordPress frontend | `http://localhost:8888` | Main UI entry point |
| Spring Boot API | `http://localhost:8080` | REST API base |
| API docs (Swagger UI) | `http://localhost:8080/api/docs` | Interactive API explorer |
| Health check | `http://localhost:8080/actuator/health` | Returns `{"status":"UP"}` |
| Metrics (Prometheus) | `http://localhost:8080/actuator/prometheus` | Scrape endpoint |

---

## Quick Start (Docker Compose — Recommended)

The fastest way to run the full stack locally.

```bash
# 1. Clone the repo and switch to the feature branch
git clone https://github.com/ahmedAliEid/demo-app.git
cd demo-app
git checkout 001-wordpress-java-kafka

# 2. Copy and configure environment variables
cp .env.example .env           # edit DB_PASSWORD and JWT_SECRET before use

# 3. Start all services (PostgreSQL · MySQL · Spring Boot · WordPress)
docker compose up -d

# 4. Wait for health checks (≈ 60 s on first run)
docker compose ps              # all services should show "healthy"

# 5. Open the app
open http://localhost:8888
```

> **JWT_SECRET** must be at least 32 characters. Never commit `.env` to git.

---

## Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DB_URL` | Yes | — | JDBC URL: `jdbc:postgresql://postgres:5432/demo_contacts` |
| `DB_USERNAME` | Yes | `demo` | PostgreSQL username |
| `DB_PASSWORD` | Yes | `demo_local` | PostgreSQL password |
| `JWT_SECRET` | Yes | `demo-local-…` | HMAC-SHA256 signing key, min 32 chars |
| `JWT_EXPIRY_SECONDS` | No | `1800` | Session lifetime in seconds (30 min) |
| `SPRING_PROFILES_ACTIVE` | No | — | Set to `test` to use H2 in-memory DB |

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
**Response 204** — Token is discarded client-side (stateless JWT).

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

| Action | User | Admin |
|--------|------|-------|
| View own contacts | Yes | Yes |
| View all contacts | No | Yes |
| Edit own contacts | Yes | Yes |
| Edit others' contacts | No | Yes |
| See all cities/countries in filters | No (own scope) | Yes (all) |

---

## User Management

There is no self-registration in Phase 1. Users must be seeded by an admin directly in the database.

```sql
-- Connect to PostgreSQL and run:
INSERT INTO users (username, password_hash, role)
VALUES (
  'jsmith',
  '$2a$12$<bcrypt-hash>',   -- generate: htpasswd -bnBC 12 "" yourpassword | tr -d ':\n' | sed 's/$apr1//'
  'user'                    -- or 'admin'
);
```

To generate a bcrypt hash via Docker without installing extra tools:

```bash
docker run --rm httpd:alpine htpasswd -bnBC 12 "" yourpassword | tr -d ':\n'
```

---

## Running Tests

### Backend

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

### WordPress Plugin

```bash
cd wordpress/wp-content/plugins/demo-contacts-api
composer install
./vendor/bin/phpunit tests/unit/
```

---

## Manual Setup (Without Docker)

If you prefer to run services directly instead of via Docker Compose:

### 1. Start PostgreSQL

```bash
docker compose up -d postgres mysql
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

### 3. Run WordPress

```bash
cd wordpress
composer install
# Install WordPress if fresh:
wp core download
wp config create --dbname=wordpress --dbuser=wordpress --dbpass=wordpress_local --dbhost=localhost
wp db create
wp core install --url=http://localhost:8888 --title="Demo Contacts" \
  --admin_user=wpadmin --admin_email=admin@example.com --admin_password=wp_admin_pass
wp plugin activate demo-contacts-api
wp theme activate contacts-child
wp option set demo_contacts_api_url "http://localhost:8080"
php -S localhost:8888
```

---

## Observability

| Endpoint | Purpose |
|----------|---------|
| `GET /actuator/health` | Liveness — confirm the app is up |
| `GET /actuator/prometheus` | Prometheus metrics scrape endpoint |

All requests log a trace ID in structured JSON (SLF4J). Filter logs by `traceId` to trace a single request through the system.

---

## Troubleshooting

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| `401 Unauthorized` on API | Token missing or expired | Log in again to get a fresh token |
| `403 Forbidden` on contact | User trying to access another user's contact | Check the contact's owner; use admin account if needed |
| `409 Conflict` on create | Duplicate full name in your scope | Use a different name |
| WordPress shows blank page | Plugin or theme not activated | Run `wp plugin activate demo-contacts-api && wp theme activate contacts-child` |
| Backend fails to start | PostgreSQL not ready | Run `docker compose up -d postgres` and wait for `healthy` status |
| JWT_SECRET error on startup | Secret too short | Set `JWT_SECRET` to a string of at least 32 characters |

---

## Project Structure

```
demo-app/
├── docker-compose.yml          # Full local stack
├── backend/                    # Spring Boot (Java 21 / Maven)
│   ├── pom.xml
│   └── src/main/java/com/demo/app/
│       ├── controller/         # REST endpoints (Auth, Contact)
│       ├── service/            # Business logic
│       ├── security/           # JWT filter + UserDetailsService
│       ├── domain/             # JPA entities (User, Contact, Address)
│       ├── dto/                # Request/response objects
│       └── exception/          # Global error handler
├── wordpress/                  # WordPress installation
│   └── wp-content/
│       ├── themes/contacts-child/       # Block child theme + page templates
│       └── plugins/demo-contacts-api/  # Spring Boot API integration plugin
├── specs/001-login-contacts/
│   ├── contracts/openapi.yaml  # Authoritative API contract
│   ├── plan.md                 # Implementation plan
│   ├── data-model.md           # Database schema
│   └── quickstart.md           # Developer quickstart
└── docs/
    └── GUIDE.md                # This file
```
