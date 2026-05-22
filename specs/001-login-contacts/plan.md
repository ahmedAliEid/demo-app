# Implementation Plan: Login & Contact Address Management

**Branch**: `001-wordpress-java-kafka` | **Date**: 2026-05-22 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-login-contacts/spec.md`

## Summary

Build a two-layer web application: a WordPress frontend (PHP 8.1+, WP 6.4+) that
presents a login page and a contact address CRUD interface, backed by a Java Spring Boot
3.x REST API (Java 21) that owns all business logic, authentication (JWT), and
persistence (PostgreSQL). Role-based access control (admin / user) gates what contacts
each user may view and modify. This is Phase 1 of a two-phase delivery; Kafka messaging
is introduced in Phase 2 without requiring refactoring of this phase's service layer.

## Technical Context

**Language/Version**:
- Backend: Java 21 (LTS), Spring Boot 3.3.x
- Frontend: PHP 8.1+, WordPress 6.4+

**Primary Dependencies**:
- Backend: `spring-boot-starter-web`, `spring-boot-starter-security`,
  `spring-boot-starter-data-jpa`, `spring-boot-starter-validation`,
  `springdoc-openapi-starter-webmvc-ui` (OpenAPI docs), `jjwt-api` + `jjwt-impl` (JWT),
  `flyway-core` (DB migrations), `testcontainers` + `testcontainers-postgresql`
- Frontend: WP REST API (built-in), Composer, custom `demo-contacts-api` plugin

**Storage**: PostgreSQL 15+ (production / staging); H2 in-memory (unit tests only)

**Testing**:
- Backend: JUnit 5, Mockito, `@SpringBootTest` + Testcontainers (PostgreSQL), Spring
  Security Test, Spring MVC Test
- Frontend: PHPUnit 10+, WP_Mock (unit); WP-CLI for integration scaffolding

**Target Platform**: Linux server; containerised (Docker Compose for local dev)

**Project Type**: Web application — WordPress frontend + Spring Boot REST API

**Performance Goals**:
- Login + contact list load < 3 s (SC-001)
- Unauthenticated redirect < 1 s (SC-005)
- Contact add round-trip < 2 s end-to-end

**Constraints**:
- Session timeout: 30 min inactivity (JWT expiry + WordPress cookie cleared)
- Pagination: 20 contacts/page default
- Last-write-wins for concurrent edits (no optimistic locking in Phase 1)
- No public self-registration; users are seeded by an admin

**Scale/Scope**: Small internal tool; ~10–50 concurrent users; no extreme throughput
requirements for Phase 1

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design — see bottom.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Layered Architecture | ✅ PASS | WordPress = Presentation only; Spring Boot = API + persistence; Kafka = Phase 2 |
| II. API-First Design | ✅ PASS | `contracts/openapi.yaml` produced before any code; DTOs used at boundary |
| III. Kafka (Phase 2+) | ✅ PASS | Not in scope; `ContactEventPublisher` interface stubbed as extension point |
| IV. Security by Default | ✅ PASS | JWT + Spring Security; `@Valid`; WP nonces + `esc_html`; no secrets in code |
| V. Test-First | ✅ PASS | Tests written before implementation; 80% coverage gate on service + domain |
| VI. Observability | ✅ PASS | Actuator `/health` + `/prometheus`; SLF4J JSON logs; trace IDs on all requests |

**Pre-design gate: ALL PASS — proceeding to Phase 0.**

## Project Structure

### Documentation (this feature)

```text
specs/001-login-contacts/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── openapi.yaml     # Phase 1 output — REST API contract
└── tasks.md             # Phase 2 output (/speckit-tasks — NOT created here)
```

### Source Code (repository root)

```text
backend/                                   # Spring Boot application (Maven)
├── pom.xml
└── src/
    ├── main/
    │   ├── java/com/demo/app/
    │   │   ├── DemoAppApplication.java
    │   │   ├── config/
    │   │   │   ├── SecurityConfig.java
    │   │   │   └── OpenApiConfig.java
    │   │   ├── controller/
    │   │   │   ├── AuthController.java
    │   │   │   └── ContactController.java
    │   │   ├── service/
    │   │   │   ├── AuthService.java
    │   │   │   ├── ContactService.java
    │   │   │   └── ContactEventPublisher.java   # Phase 2 extension point (interface)
    │   │   ├── repository/
    │   │   │   ├── UserRepository.java
    │   │   │   └── ContactRepository.java
    │   │   ├── domain/
    │   │   │   ├── User.java
    │   │   │   ├── Role.java                   # enum: ADMIN, USER
    │   │   │   ├── Contact.java
    │   │   │   └── Address.java
    │   │   ├── dto/
    │   │   │   ├── LoginRequest.java
    │   │   │   ├── LoginResponse.java
    │   │   │   ├── ContactRequest.java
    │   │   │   ├── ContactResponse.java
    │   │   │   ├── ContactSummary.java
    │   │   │   └── PagedResponse.java
    │   │   ├── security/
    │   │   │   ├── JwtTokenProvider.java
    │   │   │   ├── JwtAuthenticationFilter.java
    │   │   │   └── UserDetailsServiceImpl.java
    │   │   └── exception/
    │   │       ├── GlobalExceptionHandler.java
    │   │       ├── ContactNotFoundException.java
    │   │       └── DuplicateContactNameException.java
    │   └── resources/
    │       ├── application.yml
    │       ├── application-test.yml
    │       └── db/migration/
    │           ├── V1__create_users.sql
    │           ├── V2__create_contacts.sql
    │           └── V3__create_addresses.sql
    └── test/
        └── java/com/demo/app/
            ├── controller/        # Spring MVC tests (@WebMvcTest)
            ├── service/           # Unit tests (Mockito)
            ├── repository/        # Data layer tests (@DataJpaTest)
            └── integration/       # Full stack (@SpringBootTest + Testcontainers)

wordpress/                                 # WordPress installation root
├── composer.json
└── wp-content/
    ├── themes/
    │   └── contacts-child/               # Child theme (block/FSE)
    │       ├── style.css
    │       ├── functions.php
    │       └── templates/
    │           ├── login.html
    │           ├── contacts-list.html
    │           ├── contact-detail.html
    │           └── contact-form.html
    └── plugins/
        └── demo-contacts-api/            # API integration plugin
            ├── demo-contacts-api.php
            ├── includes/
            │   ├── class-api-client.php   # WP_Http wrapper for Spring Boot API
            │   ├── class-auth-handler.php # JWT storage in WP session/cookie
            │   └── class-contact-handler.php
            ├── assets/
            │   ├── js/contacts.js
            │   └── css/contacts.css
            └── tests/
                └── unit/
```

**Structure Decision**: Web application (Option 2 variant) — `backend/` for Spring Boot,
`wordpress/` for the WordPress installation. Separate roots allow independent deployment
and CI pipelines per layer. No shared source files cross the layer boundary.

## Post-Design Constitution Check

*Re-evaluated after Phase 1 artifacts (data-model.md, openapi.yaml) were produced.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Layered Architecture | ✅ PASS | No business logic in WordPress plugin; all validation in Spring Boot |
| II. API-First Design | ✅ PASS | `contracts/openapi.yaml` complete; all endpoints versioned under `/api/v1/` |
| III. Kafka (Phase 2+) | ✅ PASS | `ContactEventPublisher` interface stubbed; no Kafka dependency in Phase 1 |
| IV. Security by Default | ✅ PASS | JWT on all endpoints except `/api/v1/auth/login`; owner-scoped queries enforced |
| V. Test-First | ✅ PASS | Contract, integration, and unit test placeholders defined in structure |
| VI. Observability | ✅ PASS | Actuator, Micrometer, and structured logging wired in config |

**Post-design gate: ALL PASS.**
