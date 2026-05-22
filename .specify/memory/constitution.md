<!--
  SYNC IMPACT REPORT
  ==================
  Version change: [TEMPLATE] → 1.0.0
  Modified principles: All (initial population from template placeholders)
  Added sections:
    - Technology Standards (WordPress, Spring Boot, Kafka per-layer rules)
    - Development Workflow (branching, PR gates, phased delivery)
  Removed sections: None (all template stubs replaced)
  Templates reviewed:
    - .specify/templates/plan-template.md ✅ aligned (Constitution Check section present)
    - .specify/templates/spec-template.md ✅ aligned (FR/SC/Assumptions structure fits)
    - .specify/templates/tasks-template.md ✅ aligned (phased task structure fits)
  Deferred TODOs: None
-->

# Demo WordPress–Java–Kafka Constitution

## Core Principles

### I. Layered Architecture (NON-NEGOTIABLE)

The system MUST maintain strict separation across three layers:

- **Presentation** — WordPress handles all UI rendering, theming, and end-user interaction.
  WordPress core files MUST NOT be modified; all customisation goes through child themes,
  hooks, filters, and plugins only.
- **API** — Java Spring Boot owns all business logic, data persistence, and REST API exposure.
  WordPress communicates with the backend exclusively via the versioned REST API;
  direct database access from WordPress to the Spring Boot database is forbidden.
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

- **WordPress**: nonce verification on all form actions; output escaping (`esc_html`,
  `esc_attr`, `wp_kses`) everywhere; no direct `$_GET`/`$_POST` access without
  `sanitize_*` helpers; capabilities checked before privileged operations.
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
- **WordPress**: PHPUnit + WP_Mock for unit tests; WP-CLI test scaffolding for integration.
- **Kafka**: Embedded Kafka (`EmbeddedKafkaBroker`) for producer/consumer unit tests;
  Testcontainers Kafka for integration tests.
- Minimum coverage gate: 80% line coverage on Spring Boot service and domain layers.
- Tests MUST be deterministic and order-independent; no `Thread.sleep` in tests.

### VI. Observability

Every service boundary MUST emit structured, searchable signals:

- **Logging**: SLF4J + Logback (Spring Boot) and `wp_debug_log` (WordPress) with structured
  JSON format in staging/production. Log levels MUST reflect actual severity; `ERROR`
  only for actionable failures.
- **Health checks**: Spring Boot Actuator `/actuator/health` endpoint MUST be enabled and
  exposed; WordPress MUST expose a lightweight ping endpoint via the REST API.
- **Metrics**: Micrometer + Prometheus exposition (`/actuator/prometheus`) for Spring Boot;
  Kafka consumer lag MUST be exported as a metric.
- **Tracing**: Distributed trace IDs (e.g., via Micrometer Tracing / OpenTelemetry) MUST
  be propagated across the HTTP → Kafka boundary.

## Technology Standards

### WordPress Layer

- PHP version: 8.1+; WordPress version: 6.4+.
- Theme development: child themes only; block themes (FSE) preferred for new builds.
- Plugin development: namespaced PHP classes, PSR-4 autoloading, Composer for dependencies.
- WP REST API MUST be used for all AJAX interactions; `admin-ajax.php` is deprecated for
  new code.
- WordPress MUST NOT hold application state that belongs to the backend (no business logic
  in WordPress templates or shortcodes).

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
- **Phased delivery**:
  - Phase 1 delivers the WordPress UI + Spring Boot REST API as a fully functional system.
  - Phase 2 introduces Kafka; Phase 1 REST endpoints remain operational during migration.
  - No phase should leave the system in a broken or partially wired state at merge time.
- **Dependency updates**: MUST be done in an isolated PR with explicit test evidence that
  nothing regressed.

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

**Version**: 1.0.0 | **Ratified**: 2026-05-22 | **Last Amended**: 2026-05-22
