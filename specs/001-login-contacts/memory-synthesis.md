# Memory Synthesis

feature: 001-login-contacts
status: active
hard_conflicts: 0
soft_conflicts: 0

<!-- Keep every section below, even when empty. Use "- [none]" for empty sections. -->
<!-- Keep this file within retrieval.max_synthesis_words, default 900 words. -->

## Current Scope

- Feature: Login & Contact Address Management (Phase 1)
- Feature folder: specs/001-login-contacts
- Covers: authentication, contact CRUD, address management, role-based access

## Relevant Project Context

- Demo Contacts App: internal tool, two roles (admin / user), no self-registration.
- Phase 1 is REST + Laravel UI; Phase 2 adds Kafka (out of scope for this feature).
- Laravel PHP frontend (PHP 8.2, Laravel 11) consumes Spring Boot REST API via HTTP facade.

## Relevant Decisions

- ADR-001 (partially superseded): original tech stack; WordPress portion replaced by ADR-008.
- ADR-004: RBAC — admin sees all, user sees own contacts only; enforced at API layer.
- ADR-005: Duplicate name policy — HTTP 409 Conflict for duplicates within visible scope.
- ADR-006: Session timeout — 30 min; both JWT and PHP session must use the same value.
- ADR-007: Search/filter — name (substring), city and country (exact); paginated 20/page.
- ADR-008: Laravel replaces WordPress as the Presentation layer.

## Active Architecture Constraints

- No business logic in the PHP layer; all validation and data ownership is in Spring Boot.
- PHP communicates with Spring Boot exclusively via the versioned REST API (Bearer JWT).
- No direct database access from the PHP layer.
- CSRF protection required on all state-changing routes (Laravel `VerifyCsrfToken`).
- All Blade output must use `{{ }}` escaping; raw `{!! !!}` requires explicit justification.

## Accepted Deviations

- [none]

## Relevant Security Constraints

- JWT stored in Laravel server-side session (not exposed to browser JS).
- CSRF token required on all POST/PUT/DELETE routes.
- Input validated via Laravel Form Requests before forwarding to Spring Boot.
- `declare(strict_types=1)` required in every PHP file.

## Related Historical Lessons

- [none recorded yet — run `/speckit-memory-md-capture` after implementation]

## Conflict Warnings

- [none]

## Retrieval Notes

- Index entries considered: 7; source sections read: 4; budget: within limit
