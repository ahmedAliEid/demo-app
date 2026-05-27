# Research: Login & Contact Address Management

**Feature**: 001-login-contacts | **Date**: 2026-05-22

## 1. Authentication Strategy — JWT vs. Session-Based

**Decision**: Stateless JWT authentication

**Rationale**: WordPress acts as a headless consumer of the Spring Boot REST API. A
traditional server-side session requires sticky sessions or a shared session store
(Redis) between the two runtimes. JWT is stateless, travels in the `Authorization`
header on every API call from the WordPress plugin, and requires no shared state
infrastructure. The 30-minute inactivity timeout is enforced by issuing short-lived
access tokens (30 min TTL) without a refresh token for Phase 1 simplicity.

**Alternatives considered**:
- HTTP session (Spring Session): Requires shared Redis or database session store across
  WordPress and Spring Boot. Adds infrastructure dependency; rejected for Phase 1.
- OAuth2 / OIDC: Appropriate for multi-application SSO but over-engineered for a single
  internal tool. Deferred to Phase 2 if needed.

**Key implementation notes**:
- Library: `jjwt-api` + `jjwt-impl` + `jjwt-jackson`
- Token payload: `sub` (username), `role`, `iat`, `exp` (now + 30 min)
- WordPress plugin stores the JWT in a PHP session variable; sends it as
  `Authorization: Bearer <token>` on every API request via `WP_Http`
- On token expiry, the API returns `401`; the WordPress plugin clears the session
  and redirects to the login page with the expiry message

---

## 2. WordPress API Consumer Pattern

**Decision**: Custom WordPress plugin (`demo-contacts-api`) using `WP_Http`

**Rationale**: A dedicated plugin keeps API integration logic isolated from theme
presentation logic (constitution principle I). `WP_Http` is the WordPress-sanctioned
HTTP client, handles SSL, redirects, and timeouts consistently, and avoids raw `curl`
calls that bypass WordPress error handling.

**Alternatives considered**:
- Guzzle HTTP (Composer): Works but adds a Composer dependency and bypasses WP's own
  HTTP abstraction. Rejected in favour of keeping WP-native patterns.
- REST API proxy via WordPress: Using WordPress as a proxy that re-exposes Spring Boot
  endpoints. Adds latency and coupling; rejected.

**Key implementation notes**:
- `class-api-client.php`: Wraps all `WP_Http::request()` calls; sets `Authorization`
  header; centralises error mapping (4xx/5xx → WP_Error)
- `class-auth-handler.php`: Handles login form submission, stores JWT in PHP session
  (`$_SESSION['demo_jwt']`), provides `is_authenticated()` and `get_token()` helpers
- `class-contact-handler.php`: CRUD methods that call `class-api-client.php`; maps
  JSON responses to PHP arrays for template consumption
- Templates receive plain PHP arrays; no API logic in template files

---

## 3. Database — PostgreSQL + Flyway

**Decision**: PostgreSQL 15+ with Flyway for schema migrations

**Rationale**: PostgreSQL is the industry standard for relational data with strong
JSON support, full ACID guarantees, and excellent Spring Data JPA integration. Flyway
provides version-controlled, repeatable schema migrations that run automatically on
Spring Boot startup, keeping the schema in sync across dev/staging/production.

**Alternatives considered**:
- MySQL 8: Viable but PostgreSQL has superior constraint handling (e.g., `UNIQUE`
  partial indexes, `TIMESTAMPTZ`) and better community support for the Spring ecosystem.
- H2 for all environments: H2 is used for unit tests only; it lacks PostgreSQL-specific
  features used in production queries (e.g., `ILIKE` for case-insensitive search).

**Key implementation notes**:
- Spring profile `test` uses H2 in-memory; all other profiles use PostgreSQL
- Flyway migration files live at `src/main/resources/db/migration/`
- `application.yml` reads DB credentials from environment variables:
  `DB_URL`, `DB_USERNAME`, `DB_PASSWORD`
- `UNIQUE(owner_id, full_name)` enforced at DB level for user-scope uniqueness;
  admin-scope uniqueness (global) enforced at service layer before insert

---

## 4. RBAC in Spring Security

**Decision**: Method-level security with `@PreAuthorize` + custom ownership check

**Rationale**: Spring Security's method-level security (`@EnableMethodSecurity`) allows
`@PreAuthorize("hasRole('ADMIN') or @contactSecurity.isOwner(#id, authentication)")`
expressions to gate service methods. This keeps access control at the service boundary,
not scattered across controller logic.

**Alternatives considered**:
- Controller-level `if (role == ADMIN)` checks: Brittle, easy to miss on new endpoints,
  not testable in isolation. Rejected.
- Spring ACL: Full-featured but heavyweight for two roles and one domain object type.
  Rejected in favour of a simple custom `ContactSecurityService`.

**Key implementation notes**:
- `Role` enum: `ADMIN`, `USER`
- `ContactSecurityService.isOwner(contactId, authentication)`: Loads the contact and
  checks `contact.getOwner().getUsername().equals(auth.getName())`
- All `ContactService` write methods annotated with `@PreAuthorize`
- `GET /api/v1/contacts` query scopes: admin → `findAll(…)`, user →
  `findByOwnerUsername(currentUser, …)`

---

## 5. Duplicate Name Check — Scoped Uniqueness

**Decision**: DB-level unique constraint for user-scope; service-layer check for admin-scope

**Rationale**: A `UNIQUE(owner_id, full_name)` database constraint naturally prevents a
regular user from creating two contacts with the same name. For admin users (who see all
contacts), a service-layer pre-insert query checks if the name exists across all contacts,
since admin-created contacts may overlap with another user's contact names.

**Key implementation notes**:
- `ContactRepository.existsByFullNameIgnoreCase(String name)` — admin check
- `ContactRepository.existsByFullNameIgnoreCaseAndOwner(String name, User owner)` — user check
- `DuplicateContactNameException` → mapped to `409 Conflict` in `GlobalExceptionHandler`

---

## 6. Phase 2 Readiness — Event Publisher Extension Point

**Decision**: `ContactEventPublisher` interface stubbed with no-op implementation

**Rationale**: The constitution requires Phase 1 code to be designed so Kafka
producers/consumers can be added in Phase 2 without refactoring service logic.
Introducing a `ContactEventPublisher` interface that `ContactService` depends on
(injected via constructor) means Phase 2 only needs to provide a Kafka-backed
implementation — zero changes to `ContactService`.

**Key implementation notes**:
- `ContactEventPublisher` interface: `void contactCreated(Contact c)`, `void contactUpdated(Contact c)`
- `NoOpContactEventPublisher`: Default Phase 1 implementation (does nothing)
- Phase 2: Kafka implementation injected via Spring bean, no service changes needed

---

## 7. Search & Filter Implementation

**Decision**: Spring Data JPA `Specification` API for dynamic queries

**Rationale**: The contact list supports three independent filters (name text search,
city dropdown, country dropdown) that can be combined in any combination. The
`Specification` API composes predicates cleanly without proliferating repository method
variants.

**Key implementation notes**:
- `ContactSpecifications.hasNameLike(String name)`: `ILIKE '%name%'` predicate
- `ContactSpecifications.hasCity(String city)`: Exact match on `addresses.city`
- `ContactSpecifications.hasCountry(String country)`: Exact match on `addresses.country`
- `ContactSpecifications.ownedBy(User user)`: Owner scope for non-admin users
- All four composed with `Specification.where(...).and(...)` in `ContactService`
- City and country dropdown values fetched via:
  `ContactRepository.findDistinctCitiesByOwnerScope(...)` and equivalent for country
