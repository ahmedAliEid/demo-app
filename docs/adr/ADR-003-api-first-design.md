# ADR-003: API-First Design with OpenAPI 3.x

**Date**: 2026-05-22
**Status**: Accepted

## Context

WordPress and Spring Boot are developed by potentially different contributors.
Without an agreed contract upfront, the WordPress UI and the Spring Boot API
will drift: field names mismatch, error formats differ, and breaking changes go
undetected until integration. A shared contract eliminates this class of defect.

## Decision

- All Spring Boot service interfaces MUST be defined as OpenAPI 3.x contracts
  **before** implementation begins. The contract is the source of truth.
- Server stubs and client SDKs are generated from the contract, not hand-written.
- API versioning uses URI path prefixes (`/api/v1/`, `/api/v2/`); a breaking
  change requires a new version prefix, never an in-place modification.
- DTOs are used at the API boundary; JPA entities are never serialised directly
  to HTTP responses.
- All error responses follow RFC 7807 (Problem Details for HTTP APIs).
- OpenAPI docs are auto-generated and served at `/api/docs` in every environment.

## Consequences

**Positive**:
- WordPress and Spring Boot teams can work in parallel once the contract is
  agreed; WordPress can mock the API before the backend exists.
- Contract tests (Pact/Spring Cloud Contract) catch breaking changes before
  they reach integration.
- RFC 7807 errors give consumers a consistent, parseable error structure.

**Negative**:
- Upfront contract definition adds a design step before coding can begin.
- DTO maintenance overhead grows as the data model evolves; entities and DTOs
  must be kept in sync.
