# Quickstart: Login & Contact Address Management

**Feature**: 001-login-contacts | **Date**: 2026-05-22

## Prerequisites

| Tool | Minimum Version | Purpose |
|------|-----------------|---------|
| Java | 21 | Spring Boot backend |
| Maven | 3.9+ | Backend build |
| Docker + Docker Compose | 24+ | PostgreSQL for local dev |
| PHP | 8.1+ | WordPress |
| Composer | 2.x | WordPress plugin dependencies |
| WP-CLI | 2.x | WordPress management |
| Node.js | 20+ (optional) | Frontend asset building |

---

## 1. Clone and Configure

```bash
git clone https://github.com/ahmedAliEid/demo-app.git
cd demo-app
git checkout 001-wordpress-java-kafka
```

---

## 2. Start the Database

```bash
# From repo root — starts PostgreSQL 15 on port 5432
docker compose up -d postgres
```

Create a `docker-compose.yml` at the repo root (if not present):

```yaml
services:
  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: demo_contacts
      POSTGRES_USER: demo
      POSTGRES_PASSWORD: demo_local
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

volumes:
  postgres_data:
```

---

## 3. Configure and Run the Spring Boot Backend

```bash
cd backend

# Set environment variables (or copy to .env and source it — never commit .env)
export DB_URL=jdbc:postgresql://localhost:5432/demo_contacts
export DB_USERNAME=demo
export DB_PASSWORD=demo_local
export JWT_SECRET=change-me-in-production-must-be-at-least-32-chars
export JWT_EXPIRY_SECONDS=1800

# Run — Flyway migrations execute automatically on startup
./mvnw spring-boot:run
```

Verify the backend is running:

```bash
curl http://localhost:8080/actuator/health
# → {"status":"UP"}

# OpenAPI docs available at:
open http://localhost:8080/api/docs
```

---

## 4. Seed an Admin User

Spring Boot does not provide a registration endpoint in Phase 1. Seed users via
the database directly (or via a seeding SQL script in `db/migration/`):

```sql
-- Run against your local PostgreSQL instance
INSERT INTO users (username, password_hash, role)
VALUES (
  'admin',
  '$2a$12$<bcrypt-hash-of-your-password>',   -- generate with: htpasswd -bnBC 12 "" yourpassword
  'admin'
);
```

Or use the provided seed script (once created as part of implementation):

```bash
./mvnw spring-boot:run -Dspring-boot.run.arguments=--seed
```

---

## 5. Configure and Run WordPress

```bash
cd wordpress

# Install PHP dependencies
composer install

# Install WordPress (if fresh)
wp core download
wp config create \
  --dbname=wordpress_demo \
  --dbuser=root \
  --dbpass=your_wp_db_pass \
  --dbhost=localhost

wp db create
wp core install \
  --url=http://localhost:8888 \
  --title="Demo Contacts" \
  --admin_user=wpadmin \
  --admin_email=admin@example.com \
  --admin_password=wp_admin_pass

# Activate the contacts plugin
wp plugin activate demo-contacts-api

# Activate the child theme
wp theme activate contacts-child

# Set the Spring Boot API base URL (stored as WP option, not in code)
wp option set demo_contacts_api_url "http://localhost:8080"
```

Start WordPress (using PHP built-in server for local dev):

```bash
cd wordpress
php -S localhost:8888
```

Open http://localhost:8888 — you should see the login page.

---

## 6. Run Backend Tests

```bash
cd backend

# Unit tests only (fast, uses H2)
./mvnw test

# Integration tests (requires Docker for Testcontainers PostgreSQL)
./mvnw verify -Pintegration-tests

# Coverage report (opens in browser)
./mvnw verify jacoco:report
open target/site/jacoco/index.html
```

Coverage gate: **80% line coverage** on `service/` and `domain/` packages.

---

## 7. Run WordPress Tests

```bash
cd wordpress/wp-content/plugins/demo-contacts-api

# Install test dependencies
composer install

# Run PHPUnit tests
./vendor/bin/phpunit tests/unit/
```

---

## 8. Verify the Golden Path

1. Open http://localhost:8888
2. Log in with the seeded admin credentials
3. Confirm the contact list page loads (empty state message if no contacts)
4. Click "Add Contact", fill in all required fields, submit
5. Confirm the new contact appears in the list
6. Click the contact to view its detail page
7. Click "Edit", change a field, save
8. Confirm the updated value appears in the list and detail view
9. Use the name search field — confirm filtering works
10. Use the city/country dropdowns — confirm combined filtering works
11. Log out; confirm redirect to login page
12. Wait 30 minutes or manually expire the JWT; attempt to access the contacts
    page — confirm redirect to login with the expiry message

---

## 9. Environment Variables Reference

| Variable | Required | Description |
|----------|----------|-------------|
| `DB_URL` | Yes | JDBC URL for PostgreSQL |
| `DB_USERNAME` | Yes | PostgreSQL username |
| `DB_PASSWORD` | Yes | PostgreSQL password |
| `JWT_SECRET` | Yes | HMAC-SHA256 signing key (min 32 chars) |
| `JWT_EXPIRY_SECONDS` | No | JWT TTL in seconds (default: 1800) |
| `SPRING_PROFILES_ACTIVE` | No | `test` for H2; omit for PostgreSQL |
