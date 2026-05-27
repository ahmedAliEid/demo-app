<!--
  SYNC IMPACT REPORT
  ==================
  Version change: 1.0.0 → 2.1.0 (MAJOR: UI layer technology replaced; MINOR: Principle VII added)
  Modified principles:
    - I: Presentation layer changed from WordPress to Laravel PHP
    - IV: Security — WordPress-specific rules replaced with Laravel/PHP equivalents
    - V: Test-First — WordPress/PHPUnit replaced with Laravel TestCase / Pest PHP
    - VI: Observability — WordPress logging replaced with Laravel Log facade
  Added sections:
    - Technology Standards: Laravel PHP Layer (replaces WordPress Layer)
  Removed sections:
    - Technology Standards: WordPress Layer
  ADR reference: ADR-008 (2026-05-24) — supersedes ADR-001 WordPress UI decision
  Templates reviewed:
    - .specify/templates/plan-template.md ✅ aligned
    - .specify/templates/spec-template.md ✅ aligned
    - .specify/templates/tasks-template.md ✅ aligned
  Deferred TODOs: None
-->

# Demo Laravel–Java–Kafka Constitution

## Core Principles

### I. Layered Architecture (NON-NEGOTIABLE)

The system MUST maintain strict separation across three layers:

- **Presentation** — Laravel PHP handles all UI rendering, routing, and end-user
  interaction. Blade templates are used for all views; controllers handle request
  routing and response composition. No business logic lives in the PHP layer.
  All data operations go through the Spring Boot REST API via Laravel's HTTP client;
  direct database access from PHP to the Spring Boot database is forbidden.
- **API** — Java Spring Boot owns all business logic, data persistence, and REST API exposure.
  The Laravel PHP layer communicates with the backend exclusively via the versioned REST API;
  no PHP controller or service may own domain state.
- **Messaging** — Apache Kafka owns all asynchronous, event-driven communication between
  services. Synchronous REST calls MUST NOT be used for fire-and-forget or fan-out scenarios;
  those go through Kafka topics.

Crossing layer boundaries in the wrong direction is a constitution violation and MUST block merge.

### II. API-First Design

All Spring Boot service interfaces MUST be defined as OpenAPI 3.x contracts before
implementation begins. The contract is the source of truth; generated server stubs and
client SDKs derive from it — not vice versa.

Rules:
- API versioning via URI path prefix (`/api/v1/`, `/api/v2/`); breaking changes require a new version.
- DTOs MUST be used at the API boundary; JPA entities MUST NOT be serialised directly to
  the HTTP response.
- All endpoints MUST return standard problem-detail error responses (RFC 7807).
- OpenAPI docs MUST be auto-generated and accessible at `/api/docs` in every environment.

### III. Event-Driven Messaging via Kafka (Phase 2+)

Kafka topic naming convention: `{domain}.{entity}.{event}` (e.g., `orders.order.created`).

Rules:
- Producers MUST be idempotent (`enable.idempotence=true`).
- Consumers MUST commit offsets only after successful processing (manual offset commit).
- Every consumer group MUST have a Dead Letter Queue (DLQ) topic: `{original-topic}.dlq`.
- Schema contracts MUST be registered in a Schema Registry (Avro or Protobuf); untyped
  JSON messages in production are a constitution violation.
- Consumer failures MUST NOT bring down the consumer application; circuit-breaker or
  retry-with-backoff pattern is required.
- Phase 1 (REST-only) code MUST be designed so Kafka producers/consumers can be added
  without refactoring existing service logic (use the Outbox or Event Publisher pattern
  as an extension point).

### IV. Security by Default (NON-NEGOTIABLE)

All layers MUST enforce security controls appropriate to their boundary:

- **Laravel PHP**: CSRF protection via Laravel's `VerifyCsrfToken` middleware on all
  state-changing routes; output escaped via Blade's `{{ }}` syntax (raw `{!! !!}` only
  with explicit justification); all user input validated via Laravel Form Requests
  (`$request->validate()`); `declare(strict_types=1)` in every PHP file; no direct
  `$_GET`/`$_POST`/`$_SERVER` access — use Laravel's `Request` object exclusively.
- **Spring Boot**: Spring Security MUST be configured (JWT or OAuth2); no endpoint is
  unauthenticated by default — explicit `permitAll()` is the exception, not the rule;
  input validation via Bean Validation (`@Valid`) at controller layer;
  SQL injection prevention via JPA/named parameters only (no string-concatenated queries).
- **Kafka**: SASL/TLS MUST be enabled in staging and production; consumer applications
  MUST validate and sanitise all message payloads before processing.
- Secrets MUST be injected via environment variables or a secrets manager; no credentials
  in source code or committed config files.

### V. Test-First Development

Tests are written before implementation (TDD cycle: Red → Green → Refactor).

- **Spring Boot**: JUnit 5 + Mockito for unit tests; `@SpringBootTest` with Testcontainers
  for integration tests; contract tests (Spring Cloud Contract or Pact) for API boundaries.
- **Laravel PHP**: PHPUnit 11 (or Pest PHP) with Laravel's `TestCase` base class;
  `Http::fake()` for mocking Spring Boot API calls in unit tests; Laravel Dusk for
  browser integration tests where applicable.
- **Kafka**: Embedded Kafka (`EmbeddedKafkaBroker`) for producer/consumer unit tests;
  Testcontainers Kafka for integration tests.
- Minimum coverage gate: 80% line coverage on Spring Boot service and domain layers.
- Tests MUST be deterministic and order-independent; no `Thread.sleep` in tests.

### VI. Observability

Every service boundary MUST emit structured, searchable signals:

- **Logging**: SLF4J + Logback (Spring Boot) and Laravel Log facade (PHP) with structured
  JSON format in staging/production (`LOG_CHANNEL=stack`). Log levels MUST reflect actual
  severity; `ERROR` only for actionable failures.
- **Health checks**: Spring Boot Actuator `/actuator/health` endpoint MUST be enabled and
  exposed; Laravel MUST expose a lightweight `/health` route returning HTTP 200.
- **Metrics**: Micrometer + Prometheus exposition (`/actuator/prometheus`) for Spring Boot;
  Kafka consumer lag MUST be exported as a metric.
- **Tracing**: Distributed trace IDs (e.g., via Micrometer Tracing / OpenTelemetry) MUST
  be propagated across the HTTP → Kafka boundary.

### VII. Documentation Synchronization (NON-NEGOTIABLE)

Any change to a technology layer, architectural decision, or project-wide standard MUST
be reflected across all governed documentation artifacts in the **same PR** as the code
change. A PR that changes technology without updating documentation is a constitution
violation and MUST block merge.

**Governed documentation artifacts** — all of the following MUST be reviewed and updated
when a technology layer changes:

| Artifact | Location | What to update |
|---|---|---|
| Architecture Decision Record | `docs/adr/ADR-NNN-*.md` | Create a new ADR; mark superseded ADRs with status and cross-reference |
| Operator & User Guide | `docs/GUIDE.md` | Update system diagram, service URLs, setup instructions, test commands, project structure |
| Architecture Memory | `docs/memory/ARCHITECTURE.md` | Update component list, boundaries, integrations, and review date |
| Technical Decisions | `docs/memory/DECISIONS.md` | Add Active entry; mark old entry Superseded |
| Project Context | `docs/memory/PROJECT_CONTEXT.md` | Update product constraints, current priorities, and review date |
| Memory Index | `docs/memory/INDEX.md` | Update or add pointers to changed memory files |
| Constitution | `.specify/memory/constitution.md` | Update affected principles and technology standards; bump version |

**Trigger conditions** — this rule fires when a PR includes any of:
- A new or superseded ADR
- A change to the technology stack (language version, framework, runtime, or service)
- A change to inter-service communication contracts (REST, Kafka, DB ownership)
- Removal or addition of a Docker Compose service

**Validation steps for reviewers**:
1. Grep the diff for new/changed ADR files → confirm `docs/memory/DECISIONS.md` has a matching entry.
2. Confirm `docs/GUIDE.md` version date is updated and system diagram matches current services.
3. Confirm `.specify/memory/constitution.md` version is bumped and affected principles are revised.
4. Confirm `docs/memory/ARCHITECTURE.md` review date is updated.

## Technology Standards

### Laravel PHP Layer

- PHP version: 8.2+; Laravel version: 11.x (LTS).
- Code style: PSR-12; `declare(strict_types=1)` in every PHP file; constructor property
  promotion required; typed properties everywhere.
- Routing: defined in `routes/web.php` and `routes/api.php` only; no ad-hoc route
  registration inside service providers or controllers.
- Views: Blade templates only; no inline PHP (`<?php ?>`) in Blade files; component-based
  architecture (`<x-component />`) preferred over `@include` for reusable UI.
- HTTP client: Laravel's `Http` facade for all Spring Boot REST API calls; all calls MUST
  have explicit timeouts and error handling (`Http::timeout(5)->retry(3)`).
- Dependency injection: resolved via Laravel's service container; no `new ClassName()`
  inside controllers or services — use constructor injection.
- Configuration: `config/` files only; no `env()` calls outside config files; `.env` for
  local overrides, never committed.
- Dependency management: Composer with committed `composer.lock`; `composer audit` MUST
  pass in CI with no critical vulnerabilities.
- The PHP layer MUST NOT hold application state that belongs to the backend (no business
  logic in controllers, Blade templates, or service classes beyond API orchestration).

### Java Spring Boot Layer

- Java version: 21 (LTS); Spring Boot version: 3.x.
- Dependency injection: constructor injection only; field injection (`@Autowired` on fields)
  is forbidden.
- Persistence: Spring Data JPA with Hibernate; entity relationships MUST be lazy-loaded
  by default.
- Configuration: `application.yml` for defaults; environment-specific overrides via
  environment variables or Spring Cloud Config; no hardcoded environment-specific values.
- Build: Maven or Gradle (project-consistent); multi-module structure for large features.
- Exception handling: `@ControllerAdvice` global handler; no `try/catch` that swallows
  exceptions silently.

### Kafka Layer

- Kafka version: 3.x; use Spring Kafka (`spring-kafka`) for all producer/consumer wiring.
- Partition strategy: at least 3 partitions per high-volume topic; replication factor ≥ 2
  in staging/production.
- Message retention: defined per-topic based on domain requirements; defaults to 7 days.
- Consumer groups: one logical group per service consuming a topic; never share a consumer
  group across services with different processing semantics.

## Development Workflow

- **Branch naming**: `{###}-{short-description}` (e.g., `001-wordpress-java-kafka`).
- **PR gates** (all MUST pass before merge):
  - Constitution Check confirms no layer-boundary violations.
  - All tests pass (unit + integration).
  - OpenAPI contract updated if REST interface changed.
  - No secrets or credentials in diff.
  - Kafka schema changes registered in Schema Registry (Phase 2+).
  - Documentation Sync Check: if any technology layer changes, all governed documentation
    artifacts MUST be updated (see Principle VII).
- **Phased delivery**:
  - Phase 1 delivers the Laravel PHP UI + Spring Boot REST API as a fully functional system.
  - Phase 2 introduces Kafka; Phase 1 REST endpoints remain operational during migration.
  - No phase should leave the system in a broken or partially wired state at merge time.
- **Dependency updates**: MUST be done in an isolated PR with explicit test evidence that
  nothing regressed.

## Architecture Enforcement Reference

Enforceable architecture standards — layer boundaries, module rules, DTO contracts, P0 violations, and evolution policy — are defined in:

```
.specify/memory/architecture_constitution.md
```

Do not duplicate architecture enforcement rules here. This constitution defines the governance principles; `architecture_constitution.md` defines enforcement details.

Security rules, trust boundaries, and compliance mapping are defined in:

```
.specify/memory/security_constitution.md
```

## Governance

This constitution supersedes all other practices, ADRs, and team conventions unless
explicitly noted as an approved exception in the Complexity Tracking section of a plan.

Amendment procedure:
1. Raise a PR modifying this file with a clear rationale.
2. All active contributors MUST review and approve.
3. Version MUST be bumped per semantic rules (MAJOR/MINOR/PATCH).
4. Dependent templates (plan, spec, tasks) MUST be reviewed for alignment.

All PRs MUST verify compliance with each principle during review. Complexity or deviations
MUST be justified in the plan's Complexity Tracking table before a reviewer approves.

**Version**: 2.2.0 | **Ratified**: 2026-05-22 | **Last Amended**: 2026-05-24 | **Amendment**: Added Architecture Enforcement Reference section pointing to architecture_constitution.md and security_constitution.md
