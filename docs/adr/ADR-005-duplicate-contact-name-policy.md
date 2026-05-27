# ADR-005: Duplicate Contact Name Policy — Hard Block

**Date**: 2026-05-22
**Status**: Accepted

## Context

When a user submits a new contact whose name matches an existing contact within
their visible scope, three options were considered: allow silently, warn but
allow, or block with a validation error. The choice affects data quality, UX,
and the uniqueness constraint on the database.

## Decision

Duplicate contact names are **blocked**. A submission is rejected with a
validation error if a contact with the same name already exists within the
authenticated user's visible scope (i.e., within the user's own contacts for
regular users; across all contacts for admins).

The uniqueness check is scoped per visibility context, not globally across
all users. Two regular users may each have a contact named "John Smith" without
conflict; an admin or a single user cannot have two "John Smith" entries.

## Consequences

**Positive**:
- Prevents accidental duplicate entries that would confuse update and retrieval
  operations.
- Enables a database-level unique index (`UNIQUE(owner_id, name)`) for
  enforcement at the persistence layer, not just application logic.
- Simplifies search results — no need to disambiguate same-named contacts.

**Negative**:
- Real-world namesakes (two different people named "John Smith") cannot be
  stored under the same user's account without a distinguishing suffix.
  Users must append a differentiator (e.g., "John Smith (Work)").
- The uniqueness scope (per-owner vs. global) must be explicitly tested for
  both roles to avoid regression.
