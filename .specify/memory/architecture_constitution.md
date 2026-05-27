# Architecture Constitution

**Version**: 1.0.0 | **Created**: 2026-05-24 | **Project**: Demo Laravel–Java–Kafka
**Governance**: Rules in this file are enforceable architecture standards.
**Relationship**: This file complements `.specify/memory/constitution.md` (governance principles).
Do not duplicate rules from the governance constitution here.

---

## 1. Architecture Style

**Strict Layered Monolith** with three enforced runtime boundaries:

```
Presentation  →  API  →  Messaging (Phase 2)
(Laravel PHP)    (Spring Boot)   (Kafka)
```

- Dependencies flow **left to right only**: Presentation depends on API; API depends on Messaging contracts; Messaging never depends on API or Presentation.
- No layer may reach across a boundary in the wrong direction.
- The domain (business logic) lives exclusively in the **API layer**. Neither Presentation nor Messaging owns business decisions.

---

## 2. Layer Boundaries

### Presentation Layer (Laravel PHP)

**Owns**: Routing, request parsing, response rendering (Blade), session management, CSRF protection.

**Does NOT own**: Business logic, data persistence, domain validation.

**Permitted dependencies**:
- Laravel HTTP client (`Http` facade via `ApiClient`) → Spring Boot REST API
- Laravel session store → authenticated user state
- Blade templates → rendered HTML output

**Forbidden**:
- Direct database queries from PHP
- Business decisions in controllers or Blade templates (see P0 violations)
- Calling Spring Boot internals other than the public `/api/v1/*` REST surface
- Any `wp_*`, `add_action`, `add_filter`, or WordPress-specific patterns

### API Layer (Spring Boot)

**Owns**: All business logic, data persistence, validation, role enforcement, and REST contract exposure.

**Does NOT own**: HTML rendering, session management, CSRF state.

**Permitted dependencies**:
- Spring Data JPA repositories → PostgreSQL (via Service layer only)
- Kafka producers → event topics (Phase 2)

**Forbidden**:
- JPA repositories injected into controllers (see P0 violations)
- Business logic in `@Controller` or `@RestController` classes
- JPA entities serialised directly in HTTP responses (see P0 violations)
- `@Autowired` field injection — constructor injection only

### Messaging Layer (Kafka — Phase 2)

**Owns**: Asynchronous, event-driven communication between services.

**Does NOT own**: Request-response communication (that is REST's responsibility).

**Permitted dependencies**:
- Kafka topics (produce/consume)
- Schema Registry (Avro/Protobuf contracts)

**Forbidden**:
- Synchronous REST calls from the messaging layer back to the API layer
- Shared consumer groups across services with different processing semantics
- Untyped JSON messages in production (schema registry is mandatory)

---

## 3. Business Logic Placement

**All business logic lives in Spring Boot `@Service` classes.**

- `@Controller`/`@RestController`: thin dispatchers only. Receive request DTO → call service → return response DTO. No logic beyond routing.
- `@Service`: owns all validation, orchestration, domain decisions, and persistence coordination.
- JPA `@Entity` classes: pure data holders. No business methods on entities.
- Laravel PHP controllers: UI orchestration only. Call `ApiClient` → pass result to Blade view. No domain decisions.

**Decision tree for "where does this code go?"**:

```
Is it domain logic / business rule?
    → Spring Boot @Service

Is it persistence?
    → Spring Boot @Service → JpaRepository

Is it HTTP request parsing or response rendering?
    → Spring Boot @Controller (for API) or Laravel Controller (for UI)

Is it asynchronous or fire-and-forget (Phase 2)?
    → Kafka producer in @Service; consumer in dedicated listener class
```

---

## 4. Contracts & Validation

### Spring Boot API Contracts

- **Request DTOs**: All controller method parameters that accept user input MUST be typed Java record or class DTOs. Raw `Map`, `Object`, or `HttpServletRequest` body parsing is forbidden.
- **Response DTOs**: All controller return values MUST be typed response DTOs or `ResponseEntity<ResponseDTO>`. JPA entities MUST NEVER be returned directly from a controller (P0 violation).
- **Validation**: `@Valid` on all controller DTO parameters. Validation annotations on the DTO class (`@NotBlank`, `@Size`, etc.). Business-rule validation (e.g., duplicate check) inside the `@Service`.
- **Error responses**: All errors MUST use the RFC 7807 Problem Detail format via `@ControllerAdvice`. No raw exception messages in HTTP responses.
- **Mapping**: DTOs ↔ entities mapped in the Service layer. Manual mapping or MapStruct permitted; no mapping in controllers or entities.

### Laravel PHP Contracts

- **ApiClient pattern**: All Spring Boot REST calls MUST go through a dedicated service class (e.g., `app/Services/ApiClient.php` or per-domain variants). Controllers MUST NOT call `Http::get/post` directly.
- **Timeouts and retries**: Every ApiClient call MUST set an explicit timeout (`Http::timeout(N)`) and retry policy (`->retry(N, delay)`). No bare `Http::get()` without timeout.
- **Typed responses**: ApiClient methods MUST return typed PHP arrays or DTOs (readonly classes or value objects), not raw `Response` objects.
- **Form validation**: All state-changing form submissions MUST use Laravel Form Requests (`extends FormRequest`) for input validation before ApiClient is called.
- **CSRF**: All Blade form templates MUST include `@csrf`. State-changing routes MUST use the `web` middleware group which includes `VerifyCsrfToken`.

### OpenAPI Contract

- The OpenAPI 3.x contract at `specs/001-login-contacts/contracts/openapi.yaml` is the authoritative source of truth.
- The contract MUST be updated before implementation when the API interface changes.
- Generated server stubs and client SDKs derive from the contract — not the reverse.
- URI versioning: `/api/v1/`, `/api/v2/`. Breaking changes require a new version.

---

## 5. Data Access Rules

### Spring Boot

- JPA repositories (`JpaRepository` or `CrudRepository`) are injected **only into `@Service` classes**. Controllers may not hold a repository reference.
- No string-concatenated queries. All queries use JPA named parameters, JPQL, or Spring Data method names.
- Entity relationships: lazy-loaded by default (`FetchType.LAZY`). Eager loading requires explicit justification in the plan.
- Complex reads (aggregation, projection, pagination): use Spring Data Pageable + projection interfaces or a dedicated `ReadService`. Never use raw JDBC except in Flyway migration scripts.
- Configuration: `application.yml` for all JPA settings. No `persistence.xml`. No hardcoded connection strings.

### Laravel PHP

- The PHP presentation layer has **zero direct database access**. No Eloquent models, no DB facade, no raw PDO calls in the PHP frontend.
- All data originates from the Spring Boot REST API via ApiClient.

---

## 6. Async & Integration Rules (Phase 2 Forward)

**Use Kafka when**:
- An operation notifies more than one consumer (fan-out)
- An operation is fire-and-forget (caller does not need the result)
- An operation crosses a service boundary where coupling is undesirable
- An operation is not latency-sensitive and can be processed asynchronously

**Use REST when**:
- The caller needs a response (request–response pattern)
- The operation is latency-sensitive and user-facing
- The operation is strictly within a single service boundary

**Kafka implementation rules**:
- Topic naming: `{domain}.{entity}.{event}` (e.g., `contacts.contact.created`)
- Producers: `enable.idempotence=true`
- Consumers: manual offset commit after successful processing only
- Dead Letter Queue: every consumer group MUST have a `{topic}.dlq` topic
- Schema Registry: all messages MUST be registered as Avro or Protobuf schemas
- Consumer failures: circuit-breaker or retry-with-backoff required; failure must not crash the consumer application
- **Outbox pattern**: Spring Boot services MUST publish events through an Outbox or Event Publisher pattern so Kafka can be added in Phase 2 without refactoring service logic

**Phase 1 rule**: No Kafka producers or consumers exist in Phase 1. The Outbox/Event Publisher extension point must be wired as a no-op that Kafka can plug into in Phase 2.

---

## 7. Module Boundaries (Spring Boot Packages)

- Packages represent bounded contexts. Cross-package access is **only permitted through `@Service` interfaces**, not direct class references.
- Package structure:
  ```
  com.demo.app.
  ├── controller/    ← HTTP entry points (delegates to service)
  ├── service/       ← Business logic (owns domain decisions)
  ├── domain/        ← JPA entities and value objects (no business methods)
  ├── dto/           ← Request/response DTOs (no business logic)
  ├── repository/    ← Spring Data JPA (injected into service only)
  ├── security/      ← JWT filter, UserDetailsService
  └── exception/     ← @ControllerAdvice, domain exceptions
  ```
- `controller` package may reference `dto` and call `service` interfaces only.
- `service` package may reference `dto`, `domain`, and `repository`.
- `domain` package must not reference `dto`, `controller`, or `repository`.
- `repository` package must not reference `controller`, `service`, or `dto`.

---

## 8. Framework-Specific Architecture Rules

### Laravel PHP Layer

- **Routing**: All routes defined in `routes/web.php` (UI) or `routes/api.php`. No route registration in service providers, middleware, or controllers.
- **Controllers**: One public method per user action (thin). Maximum responsibility: validate input via Form Request → call ApiClient method → pass data to Blade view or redirect.
- **Blade templates**: No PHP logic blocks (`<?php ?>`). Use Blade directives only (`@if`, `@foreach`, `@auth`). Component-based UI (`<x-component />`) preferred over `@include` for reusable UI.
- **Config**: `env()` calls permitted only in `config/` files. Controllers and services must use `config('key')` — never `env()` directly.
- **Dependency injection**: resolved via Laravel service container only. No `new ClassName()` inside controllers or services.
- **HTTP client**: `Http::timeout(N)->retry(N)` pattern through `ApiClient`. No raw `curl`, `file_get_contents`, or `\Guzzle\Http\Client` instantiation.
- **Authentication state**: JWT token stored in Laravel server-side session only (`$request->session()->put('token', ...)`). Token MUST NOT be exposed in JavaScript, cookies, or HTML output.

### Spring Boot API Layer

- **Constructor injection only**: `@Autowired` on fields is forbidden. All dependencies injected via constructor.
- **Exception handling**: `@ControllerAdvice` global handler for all exceptions. No silent `try/catch` blocks that swallow exceptions. Domain exceptions extend a base `DemoAppException`.
- **Configuration**: `application.yml` for defaults; environment-specific overrides via environment variables or Spring profiles. No hardcoded environment-specific values in source.
- **Logging**: SLF4J + Logback. Structured JSON in staging/production. `ERROR` level only for actionable, operations-team-facing failures. Never log PII or credentials.
- **Health**: Spring Boot Actuator `/actuator/health` must be enabled and accessible. Prometheus metrics at `/actuator/prometheus`.

---

## 9. Blocking Architecture Violations (P0)

The following violations MUST block merge. No exception without an explicit, approved deviation recorded in the feature plan.

| ID | Violation | Layer |
|----|-----------|-------|
| P0-01 | Business logic in a Laravel PHP controller (domain decisions, calculations, rule enforcement) | Presentation |
| P0-02 | Business logic in a Blade template (PHP control flow that is not purely rendering) | Presentation |
| P0-03 | JPA entity class (`@Entity`) serialised directly as an HTTP response body | API |
| P0-04 | `@RestController` or `@Controller` directly injecting a JPA repository | API |
| P0-05 | PHP controller calling `Http::get/post` directly (bypassing `ApiClient`) | Presentation |
| P0-06 | Cross-layer database access: PHP layer accessing PostgreSQL directly | Presentation |
| P0-07 | Untyped Kafka message (raw JSON without schema registry registration) in production | Messaging |
| P0-08 | Synchronous REST call replacing a Kafka event in a fan-out or fire-and-forget scenario (Phase 2) | Messaging |
| P0-09 | Secret or credential committed to source code or config file | Any |
| P0-10 | `@Autowired` field injection in any Spring Boot class | API |

---

## 10. Architecture Evolution Policy

Architecture rules in this file may evolve when the project or technology changes.

**Governance process for any rule change**:
1. Raise a PR that modifies this file (`architecture_constitution.md`) with:
   - A clear written rationale for the change
   - Which existing rules are affected or superseded
   - A corresponding ADR if the change involves a technology or layer decision
2. All active contributors MUST review and approve the PR before merge.
3. Version number in this file MUST be bumped (MAJOR for breaking changes to boundaries, MINOR for new rules, PATCH for clarifications).
4. If an ADR is created, update `docs/memory/DECISIONS.md` and `docs/memory/INDEX.md` per Principle VII of `constitution.md`.

**What is NOT an evolution trigger**:
- A single feature deviating from a rule (that is a deviation, not evolution — document it in the feature plan)
- A preference or style change without architectural impact

---

## 11. Refactor & Drift Handling

**Intentional deviations**: If a feature must deviate from a rule in this constitution, the deviation MUST be documented in the feature plan's Complexity Tracking table before a reviewer approves. Document: which rule is deviated from, why, and the exit plan (when it will be corrected).

**Detected drift**: When a reviewer or AI agent detects architecture drift (code not matching rules), the reviewer MUST:
1. Flag the specific rule violated (reference the ID from §9 or the relevant section)
2. Request correction before merge (for P0) or document as technical debt (for lower priority)
3. NOT silently accept drift as the new standard

**Architecture Update Proposals**: If the same drift is detected in 3+ features, generate a Constitution Update Proposal targeting this file. NEVER automatically update this file. The proposal requires the full evolution governance process (§10).
