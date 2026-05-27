# ADR-006: Session Timeout — 30 Minutes of Inactivity

**Date**: 2026-05-22
**Status**: Accepted

## Context

The application handles internal contact data. Session duration is a security
vs. convenience tradeoff: a shorter timeout reduces exposure if a user leaves
a session open, while a longer timeout reduces login friction. Three options
were evaluated: 30-minute inactivity timeout, 8-hour working-day timeout, and
24-hour persistent session.

## Decision

Sessions expire after **30 minutes of inactivity**. Activity resets the timer.
When a session expires:

- The next request to a protected resource redirects the user to the login page.
- A notification message is shown: *"Your session has expired. Please log in
  again."*
- Any unsaved form data at the time of expiry is lost (no draft recovery in
  Phase 1).

## Consequences

**Positive**:
- Reduces the window of exposure for unattended or shared workstations.
- Aligns with common security baselines for internal web applications handling
  personally identifiable information (contact addresses).

**Negative**:
- Users performing slow data-entry tasks (e.g., filling a long form) may be
  timed out and lose unsaved input. Mitigation: consider adding a client-side
  inactivity warning at 25 minutes (Phase 2 enhancement).
- The backend must track last-activity timestamps and invalidate tokens
  server-side, not just rely on client-side token expiry.
