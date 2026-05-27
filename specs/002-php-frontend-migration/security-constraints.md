---
document_type: security-review
review_type: plan
assessment_date: 2026-05-24
codebase_analyzed: specs/002-php-frontend-migration
total_files_analyzed: 7
total_findings: 6
overall_risk: MODERATE
critical_count: 0
high_count: 0
medium_count: 2
low_count: 4
informational_count: 0
owasp_categories: [A01, A04, A05, A07]
cwe_ids: [CWE-307, CWE-384, CWE-400, CWE-614, CWE-693, CWE-250]
---

# Security Constraints — 002-php-frontend-migration

Generated: 2026-05-24 | Risk: MODERATE | 0 Critical · 0 High · 2 Medium · 4 Low

## Active Security Constraints for Implementation

These constraints MUST be satisfied by all implementation tasks. Treat them as requirements, not suggestions.

### MUST (Medium — merge-blocking if unresolved)

**SEC-001 — Session Regeneration on Login** (CWE-384, A01)
- `AuthController::login`: call `$request->session()->regenerate()` after storing token and role, before redirecting.
- `AuthController::logout`: call `$request->session()->invalidate()` then `$request->session()->regenerateToken()`.
- `LoginTest.php`: assert session ID changes between unauthenticated and post-login requests.

**SEC-002 — Rate Limiting on Login Route** (CWE-307, A07)
- Apply `throttle:6,1` middleware to `POST /login` (6 attempts per minute per IP).
- Return HTTP 429 with Retry-After header on excess.
- `LoginTest.php`: assert 7th attempt within 60 seconds returns 429.

### SHOULD (Low — must be resolved in same sprint)

**SEC-003 — ApiClient Retry Scope** (CWE-400, A04)
- `ApiClient::request()`: only retry on connection errors and 5xx responses.
- Do NOT retry 401, 409, or other 4xx responses.
- Implementation: `->retry(3, 500, fn($e, $req) => !($e instanceof RequestException && in_array($e->response?->status(), [401, 400, 403, 404, 409, 422])))`

**SEC-004 — Nginx Security Headers** (CWE-693, A05)
- Add to `nginx/default.conf`: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-XSS-Protection: 1; mode=block`, `server_tokens off`.

**SEC-005 — PHP-FPM Non-Root Execution** (CWE-250, A05)
- `frontend/Dockerfile`: chown `storage/` and `bootstrap/cache/` to `www-data`; add `USER www-data`.
- PHP ini: `expose_php = Off`, `display_errors = Off`.

**SEC-006 — Session Cookie Attributes** (CWE-614, A01)
- `.env.example`: document `SESSION_SECURE_COOKIE=false` (true in production), `SESSION_SAME_SITE=lax`, `SESSION_HTTP_ONLY=true`.

## Confirmed Secure-by-Design Patterns

- JWT in server-side session only — never in HTML, JS, or cookies
- CSRF via `VerifyCsrfToken` + `@csrf` in every form
- All input via Laravel Form Requests
- All HTTP calls via `ApiClient` only (P0-05 enforced)
- Zero PHP database access (P0-06 enforced)
- `declare(strict_types=1)` in every file
- `composer audit` gated in Definition of Done
- Error pages hide stack traces (`errors/500.blade.php`)
