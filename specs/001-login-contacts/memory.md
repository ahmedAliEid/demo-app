# Feature Memory — 001-login-contacts

## Scope Notes

- Phase 1 only: login flow + contact address management with role-based access.
- No self-registration; users are seeded by an admin directly in the database.
- Duplicate full names within a user's visible scope are rejected (ADR-005).
- Session timeout is 30 minutes; both Laravel session and JWT token must stay in sync.

## Relevant Durable Memory

- Constitution v2.1.0: Presentation layer is Laravel PHP (ADR-008); no WordPress code.
- ADR-004: Role-based access — `admin` sees all contacts, `user` sees only their own.
- ADR-005: Duplicate contact name policy — conflict returned as HTTP 409.
- ADR-006: Session timeout — 30 minutes inactivity; enforced at both API and UI layers.
- ADR-007: Contact search and filter — substring match on name; exact match on city/country.

## Open Questions

- [none]

## Watchlist

- Ensure Laravel session lifetime (`SESSION_LIFETIME`) matches JWT expiry (`JWT_EXPIRY_SECONDS`); mismatch causes confusing mid-session 401 errors.
- Role enforcement is at the Spring Boot API layer only; the PHP frontend MUST NOT make role-based data decisions independently.

## Never Store Here

- Permanent project decisions (those go in `docs/memory/DECISIONS.md`)
- General bug patterns (those go in `docs/memory/BUGS.md`)
- Implementation history after the feature ships
