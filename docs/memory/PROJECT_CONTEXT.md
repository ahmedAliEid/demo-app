# Project Context

Last reviewed: 2026-05-24

## Product / Service

Demo Contacts App: an internal web application for managing contact address books with
role-based access control. Two user roles: **admin** (sees/manages all contacts) and
**user** (sees/manages only their own contacts). Delivered in two phases: Phase 1
(functional UI + REST API, currently active); Phase 2 (Kafka event-driven messaging).

## Key Constraints

- **Layer separation is non-negotiable**: The PHP frontend MUST NOT contain business logic
  or direct database access; all data operations go through the Spring Boot REST API.
- **API-first**: Spring Boot REST contracts (OpenAPI 3.x) are defined before implementation;
  the contract is the source of truth.
- **No self-registration**: Users are seeded by an admin directly in the database; there is
  no sign-up flow in Phase 1.
- **PHP 8.2+ strict types**: All PHP files must declare `strict_types=1`; no `wp_*` or
  CMS-specific patterns are permitted (WordPress has been removed — see ADR-008).
- **Phase 2 readiness**: Spring Boot code must use the Outbox/Event Publisher pattern so
  Kafka producers can be added in Phase 2 without refactoring existing service logic.

## Important Domains

- **Authentication**: JWT issued by Spring Boot; stored in Laravel server-side session.
  Session and token lifetime both default to 30 minutes.
- **Contacts**: Core entity. Has sub-entity `Address`. Duplicate full names within a user's
  visible scope are rejected (ADR-005).
- **Role-based access**: `admin` role accesses all contacts; `user` role is scoped to their
  own contacts only. Enforced at the Spring Boot API layer (ADR-004).

## Current Priorities

- Complete PHP frontend migration (feature branch: `002-php-frontend-migration`): replace
  all WordPress code with Laravel 11 (ADR-008).
- Maintain full functional parity with the Phase 1 WordPress UI before closing the branch.
- Keep Phase 2 (Kafka) integration points clean and ready for extension.

## Keep Here

- Durable product constraints
- Domain language and invariants (duplicate policy, role semantics, session rules)
- Project-wide priorities that shape feature trade-offs

## Never Store Here

- Feature-specific acceptance criteria (those go in `specs/*/spec.md`)
- Task lists
- Transient implementation notes
- Changelog entries

Update the review date when constraints or priorities materially change.
