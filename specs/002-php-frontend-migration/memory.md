# Feature Memory — 002-php-frontend-migration

## Scope Notes

- Technology swap only: replace WordPress with Laravel 11 (PHP 8.2+).
- Full functional parity with Phase 1 WordPress UI is required before closing this branch.
- Docker Compose must be updated: remove WordPress + MySQL, add PHP-FPM + Nginx.
- All WordPress-specific files (`wp_*`, `functions.php`, plugin code, theme code) must be removed.
- No redesign; the existing UI look-and-feel is preserved.

## Relevant Durable Memory

- ADR-008 (Active): Laravel replaces WordPress; rationale, framework choice, consequences all documented.
- ADR-001 (Partially Superseded): WordPress UI portion superseded by ADR-008.
- ADR-002 (Updated): Presentation layer is now Laravel PHP; layer boundary unchanged.
- Constitution v2.1.0: Principle I updated (Laravel), Principle VII added (Documentation Synchronization).
- All six documentation artifacts were updated in this session per Principle VII.

## Open Questions

- Which PHP-FPM Docker image should be used (official `php:8.2-fpm` or a custom image)?
- Should Laravel Dusk be included for browser integration tests in the initial migration, or deferred to a follow-up?

## Watchlist

- Session/token lifetime sync: `SESSION_LIFETIME` (Laravel, minutes) must equal `JWT_EXPIRY_SECONDS / 60` (Spring Boot, seconds).
- Grep for any remaining `wp_`, `WP_`, `add_action`, `add_filter`, `functions.php` references before marking migration complete.
- `APP_KEY` must be set before the Laravel app starts; missing key causes silent encryption failures.
- CSRF token must be included in all Blade form templates (`@csrf`).

## Never Store Here

- Permanent project decisions (those go in `docs/memory/DECISIONS.md`)
- General bug patterns (those go in `docs/memory/BUGS.md`)
- Implementation history after the feature ships
