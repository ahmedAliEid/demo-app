# Security Constitution

**Version**: 1.1 | **Created**: 2026-05-21 | **Last Amended**: 2026-05-24 | **Change**: Auth mechanism corrected from API Key to JWT Bearer; Laravel-specific CSRF and session rules added

---

## 1. Trust Boundaries

| Zone | Description |
|---|---|
| **Untrusted** | All inbound HTTP requests from browsers and REST API clients. Treat all input as hostile until validated. |
| **Trusted** | Internal server-side logic, database layer, and background jobs. Never expose these directly to the public web. |
| **Semi-trusted** | Authenticated API key holders — validate key on every request; never assume a valid key grants elevated trust beyond its assigned role. |

- All public entry points (web app, REST API) are untrusted by default.
- Internal services and the database must never be reachable directly from the public internet.
- Single-tenant architecture — no cross-user data isolation concerns, but per-user data access must still be enforced via RBAC.

---

## 2. Authentication & Authorization Standards

- **Mechanism**: JWT Bearer authentication (HMAC-SHA256 signed, Spring Boot issues tokens).
  - Tokens passed via `Authorization: Bearer <jwt>` header on all Spring Boot API calls. Never in query strings, request bodies, or HTML output.
  - Tokens validated server-side on every request by the Spring Boot JWT filter chain.
  - Tokens are stateless; revocation is by expiry only (default 30 min, `JWT_EXPIRY_SECONDS`).
  - `JWT_SECRET` must be at least 32 characters; must be injected via environment variable; never hardcoded.
- **Laravel session**: The JWT token is stored in the Laravel server-side session only (`session('token')`). It MUST NOT be exposed to browser JavaScript, HTML, or cookies.
- **CSRF**: Laravel's `VerifyCsrfToken` middleware is active on all `web` routes. All Blade forms must include `@csrf`. State-changing routes MUST NOT disable CSRF protection.
- **Roles**: Admin / User RBAC. Two roles only in Phase 1.
  - Every Spring Boot endpoint must declare its minimum required role via Spring Security configuration.
  - Authorization checks happen at the Spring Boot API layer only. The PHP frontend MUST NOT make role-based data decisions independently.
  - Admin-only routes must be explicitly guarded — fail closed (deny by default).
- **Failure**: On auth failure, return `401 Unauthorized` or `403 Forbidden`. Never expose why authentication failed (e.g., "token expired" vs "token invalid"). Return generic problem-detail responses only.

---

## 3. Data Privacy Rules

- **PII** (names, emails): Must not appear in logs, error messages, or API responses beyond what is explicitly required. Mask or redact in logs.
- **Financial data**: Must be handled via a PCI-compliant third-party processor (e.g., Stripe). Raw card data must never touch application servers.
- **API keys / secrets**: Never log, serialize to JSON responses, or expose in error output. Rotate immediately if suspected of compromise.
- Data at rest: Sensitive fields (PII, financial references) should be encrypted or stored via a trusted provider — not in plaintext columns.
- Data in transit: TLS 1.2+ required on all endpoints. HTTP must redirect to HTTPS.

---

## 4. Secrets Management Policy

- All secrets (API keys, DB credentials, third-party tokens) are injected via **environment variables** at runtime.
- `.env` files must be listed in `.gitignore` — **never committed to version control**.
- No secret may be hardcoded in source code, configuration files, or test fixtures.
- Secrets must not appear in logs, stack traces, or error responses.
- Provide a `.env.example` file with placeholder values documenting required variables — no real values.

---

## 5. Secure-by-Design Patterns

- **SQL / ORM queries**: All database queries must use parameterized queries or ORM-provided query builders. Raw string interpolation into queries is forbidden.
- **Input validation**: All input from untrusted sources (request body, query params, headers) must be validated and sanitized before use.
- **Output encoding**: All data rendered in HTML responses must be escaped to prevent XSS. Use framework-provided templating — never concatenate raw user input into HTML.
- **Error handling**: Production error responses must not include stack traces, internal file paths, or database error details.
- **CORS**: Restrict `Access-Control-Allow-Origin` to known, explicitly allowed origins. Wildcard (`*`) is forbidden on authenticated endpoints.
- **Rate limiting**: All public-facing API endpoints must have rate limiting to prevent brute-force and abuse.
- **CSRF**: For any state-changing web app endpoints, enforce CSRF protection (e.g., CSRF tokens or `SameSite=Strict` cookies).

---

## 6. API & Integration Security

- All REST API endpoints must validate the `Content-Type` header for POST/PUT/PATCH requests.
- Outbound HTTP calls to third-party services must use TLS and validate server certificates — disable self-signed cert bypass.
- Webhook endpoints must validate incoming signatures (e.g., HMAC secret) before processing payloads.
- Third-party API keys must be scoped to minimum required permissions.

---

## 7. Audit, Logging & Monitoring

- Log all authentication events: key creation, key usage (successful and failed), key revocation.
- Log all authorization failures with the requesting identity (masked) and the resource attempted.
- Log all admin actions with timestamp and actor.
- Do not log: raw API keys, passwords, PII beyond masked identifiers, financial data.
- Logs must be written to a persistent, append-only store — not only to stdout in production.

---

## 8. Compliance Mapping (OWASP Top 10)

| OWASP Risk | Mitigation |
|---|---|
| A01 Broken Access Control | RBAC enforced server-side; fail closed; admin routes explicitly guarded |
| A02 Cryptographic Failures | TLS required; secrets never in plaintext; sensitive fields encrypted |
| A03 Injection | Parameterized queries; input validation on all untrusted input |
| A04 Insecure Design | Trust boundaries defined; principle of least privilege for roles and secrets |
| A05 Security Misconfiguration | No hardcoded secrets; CORS restricted; error details hidden in production |
| A06 Vulnerable Components | Keep dependencies updated; audit with `npm audit` / equivalent |
| A07 Auth Failures | API keys hashed at rest; keys validated server-side every request; rate limiting |
| A08 Software & Data Integrity | Webhook signatures validated; no unsigned payloads processed |
| A09 Logging Failures | Auth events, admin actions, and failures logged; no sensitive data in logs |
| A10 SSRF | Outbound requests must use allowlists; TLS cert validation enforced |
