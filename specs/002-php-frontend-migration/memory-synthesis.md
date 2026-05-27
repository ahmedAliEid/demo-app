# Memory Synthesis

feature: 002-php-frontend-migration
status: draft
hard_conflicts: 0
soft_conflicts: 0

<!-- Keep every section below, even when empty. Use "- [none]" for empty sections. -->
<!-- Keep this file within retrieval.max_synthesis_words, default 900 words. -->

## Current Scope

- Feature: PHP Frontend Migration — Replace WordPress with Laravel
- Feature folder: specs/002-php-frontend-migration
- Covers: remove all WordPress code, create Laravel app, update Docker Compose, maintain functional parity

## Relevant Project Context

- Migration is a technology swap; no new business features are added.
- All existing Phase 1 pages (login, contacts list, contact detail, address management) must work identically after migration.
- The Spring Boot REST API and PostgreSQL layer are unchanged and out of scope.

## Relevant Decisions

- ADR-008 (Active): Laravel 11 replaces WordPress. Framework: Laravel 11, PHP 8.2+, Blade, Http facade.
- ADR-001 (Partially Superseded): WordPress UI decision superseded; Java Spring Boot + Kafka decisions remain Active.
- ADR-002 (Updated): Presentation layer = Laravel PHP; boundaries and rules unchanged.
- Constitution v2.1.0 Principle VII: all governed documentation artifacts must be updated in the same PR as any technology layer change.

## Active Architecture Constraints

- PHP layer: UI rendering only. No business logic, no direct DB access.
- All data goes through Spring Boot REST API via `Http::timeout(5)->retry(3)`.
- Blade `{{ }}` escaping everywhere; `{!! !!}` requires explicit justification.
- CSRF protection via `VerifyCsrfToken` middleware on all state-changing routes.
- `declare(strict_types=1)` in every PHP file.
- No `env()` calls outside `config/` files.
- Composer with committed `composer.lock`; `composer audit` must pass in CI.

## Accepted Deviations

- [none]

## Relevant Security Constraints

- CSRF token required on all POST/PUT/DELETE form submissions (`@csrf` in every Blade form).
- JWT stored in Laravel server-side session only; not exposed to browser JavaScript.
- All user input validated via Laravel Form Requests before forwarding to API.
- Laravel `Http` facade used with explicit timeouts; no raw `curl` or `file_get_contents`.

## Related Historical Lessons

- [none recorded yet — run `/speckit-memory-md-capture` after implementation]

## Conflict Warnings

- [none]

## Retrieval Notes

- Index entries considered: 6; source sections read: 4; budget: within limit
