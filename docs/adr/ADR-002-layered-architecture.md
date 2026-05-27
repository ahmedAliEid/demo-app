# ADR-002: Layered Architecture — Strict Layer Separation

**Date**: 2026-05-22
**Status**: Accepted

## Context

With three distinct technology choices (Laravel PHP, Spring Boot, Kafka), there is
a risk of logic leaking across layers: business rules in PHP controllers or Blade
templates, direct database access bypassing the API, or synchronous REST calls
replacing Kafka where async is appropriate. Without an explicit boundary contract,
the architecture will erode over time.

## Decision

The system enforces three non-negotiable layers:

1. **Presentation** (Laravel PHP): UI rendering only. No business logic, no direct
   database access. All data operations go through the Spring Boot REST API via
   Laravel's HTTP client. Blade views handle rendering; controllers handle routing.
2. **API** (Spring Boot): All business logic, validation, and persistence. Exposes
   versioned REST endpoints. Does not push rendering concerns back to the PHP layer.
3. **Messaging** (Kafka): All fire-and-forget, fan-out, and async workflows.
   Synchronous REST MUST NOT be used where Kafka is the right fit.

Crossing layer boundaries in the wrong direction is a constitution violation
that blocks merge.

## Consequences

**Positive**:
- Each layer can be scaled, tested, and deployed independently.
- The PHP presentation layer can be swapped for a different frontend without touching Spring Boot.
- Kafka consumers can be added in Phase 2 without changing the REST API surface.

**Negative**:
- All PHP UI features require a round-trip through the REST API, adding latency
  compared to direct database access.
- Developers must resist the temptation to add "just a small bit of logic" in
  Laravel controllers or Blade templates; those belong in the Spring Boot API layer.
