# ADR-008: Frontend Migration — Replace WordPress with Laravel (PHP)

**Date**: 2026-05-24
**Status**: Accepted
**Supersedes**: ADR-001 (WordPress UI portion only)

## Context

ADR-001 selected WordPress as the Presentation layer for Phase 1. A new requirement
has been received to replace WordPress with a native PHP framework. The motivations are:

- WordPress carries significant CMS overhead (plugin ecosystem, WP core update surface,
  `wp_*` function lock-in) that is unnecessary for a purpose-built REST consumer.
- WordPress templates mix CMS concerns with application rendering; a proper MVC framework
  enforces cleaner separation of routing, controllers, and views.
- Security hardening is easier on a lightweight PHP framework than on WordPress, where
  core hooks and third-party plugins expand the attack surface.
- A native PHP framework aligns better with modern PHP 8.x best practices (typed
  properties, match expressions, fibers, readonly classes) without WordPress compatibility
  constraints.

## Decision

Replace WordPress with **Laravel 11 (LTS)** as the Presentation layer framework.

- **Runtime**: PHP 8.2+ with Laravel 11.x.
- **Templating**: Blade template engine for all view rendering.
- **HTTP client**: Laravel's built-in HTTP facade (Guzzle-backed) for all Spring Boot
  REST API calls; no direct database access from the PHP layer.
- **Routing**: Laravel route definitions only; no CMS-style URL rewriting.
- **Authentication**: Laravel's session-based auth guards consume the Spring Boot JWT
  tokens; the PHP layer holds no credentials or business state.
- **Code style**: PSR-12, strict types (`declare(strict_types=1)` in every file),
  constructor property promotion, named arguments where clarity is improved.
- **Dependency management**: Composer with version-locked `composer.lock`.
- **Testing**: PHPUnit 11 + Laravel's `TestCase` base class; Pest PHP permitted as an
  alternative test runner.

## Rationale for Laravel over alternatives

| Framework   | Reason not chosen                                           |
|-------------|-------------------------------------------------------------|
| Slim        | Too minimal; lacks built-in auth, templating, and HTTP client needed for this scope |
| Symfony     | Higher boilerplate for a frontend-only consumer; Laravel's defaults are sufficient  |
| CodeIgniter | Smaller ecosystem, less opinionated; more configuration required for security        |
| Laminas     | Enterprise complexity not warranted; steeper learning curve without clear benefit    |

## Consequences

**Positive**:
- Clean MVC separation with explicit routes, controllers, and Blade views replaces
  WordPress shortcode/template mixing.
- Laravel's HTTP client wraps all API calls with retry, timeout, and error-handling
  defaults that previously required custom WordPress code.
- PHP 8.2 typed properties and strict mode catch entire classes of bugs at parse time.
- Reduced attack surface: no WordPress plugin ecosystem, no `admin-ajax.php`, no
  unauthenticated REST namespace exposure.
- First-class `php artisan` CLI for scaffolding, migrations (if any local state is
  ever added), and testing.

**Negative**:
- Existing WordPress templates, shortcodes, and `functions.php` customisations must be
  rewritten as Blade views and Laravel controllers — non-trivial migration effort.
- Docker Compose update required: replace the `wordpress` + `mysql` services with a
  PHP-FPM + Nginx stack.
- Team members familiar only with WordPress theming will have a learning curve on
  Laravel's service container and facade system.
- CI/CD pipeline must add `composer install` and Laravel-specific steps.
