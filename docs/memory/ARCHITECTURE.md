# Architecture

Last reviewed: 2026-05-24

## System Overview

Three-layer web application for managing contacts with role-based access control.

```
Browser → Nginx → Laravel PHP (Presentation) → Spring Boot REST API (Business) → PostgreSQL
```

Phase 2 (future): Spring Boot → Kafka → Consumer Services.

## Major Components

- **Laravel PHP Frontend** (`frontend/`): PHP 8.2, Laravel 11, PHP-FPM. Handles all UI
  rendering via Blade templates. Consumes Spring Boot REST API via Laravel's Http facade.
  No business logic; no direct database access.
- **Spring Boot REST API** (`backend/`): Java 21, Spring Boot 3.3. Owns all business
  logic, validation, persistence, and JWT-based authentication. Exposes versioned REST
  endpoints under `/api/v1/`.
- **PostgreSQL 15** (`demo_contacts` DB): Owned exclusively by Spring Boot. Tables:
  `users`, `contacts`, `addresses`. Schema managed by Flyway migrations (V1–V3).
- **Nginx**: Reverse proxy terminating port 8888 and forwarding to PHP-FPM on port 9000.

## Boundaries

- **Presentation → API**: Laravel makes HTTP calls to Spring Boot using Bearer JWT tokens.
  No other cross-layer communication is permitted.
- **API → DB**: Spring Boot accesses PostgreSQL exclusively via Spring Data JPA (no raw
  JDBC string concatenation, no direct queries from the PHP layer).
- **Messaging boundary** (Phase 2 only): Spring Boot publishes events to Kafka topics;
  consumer services subscribe. The PHP layer never interacts with Kafka directly.

## Integrations

- Laravel `Http` facade → Spring Boot `/api/v1/*` REST endpoints (JSON over HTTP)
- Spring Boot → PostgreSQL 15 (JDBC via HikariCP connection pool)
- Spring Boot → Kafka (Phase 2; Outbox/Event Publisher pattern from day one)

## Risks / Complexity Hotspots

- **Latency budget**: Every PHP page render requires at least one round-trip to the Spring
  Boot API; pages that aggregate multiple resources make multiple serial API calls.
- **Session/token handoff**: Laravel stores the Spring Boot JWT in the server-side session;
  token expiry and session expiry must stay in sync (both default to 30 min).
- **CSRF vs JWT boundary**: Forms submit to Laravel (CSRF-protected); Laravel forwards to
  Spring Boot (JWT-protected). CSRF tokens are managed by Laravel only.

## Keep Here

- Stable service boundaries and ownership lines
- Cross-layer communication contracts (HTTP, not implementation details)
- Integration constraints that affect multiple features

## Never Store Here

- Step-by-step implementation plans (those go in `specs/*/plan.md`)
- One-off feature details
- Stale diagrams without current boundaries

Update the review date when boundaries, ownership, or integrations materially change.
