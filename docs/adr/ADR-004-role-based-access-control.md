# ADR-004: Role-Based Access Control — Admin vs. User Scope

**Date**: 2026-05-22
**Status**: Accepted

## Context

The contacts feature is used by multiple authenticated users. A design choice
was needed: should all users share one global contact list, should each user
manage only their own private contacts, or should access be role-differentiated?
The options carry different data-model and security implications.

## Decision

Two roles are supported: `admin` and `user`.

- **Admin**: Can view, create, update, and (in future) delete any contact
  regardless of who created it.
- **User**: Can view, create, and update only contacts they personally created.
  Contacts owned by other users are invisible to regular users.

The `Contact` entity carries an `owner` field (foreign key to the creating
User). All queries for non-admin users are scoped by `owner = current_user`.

## Consequences

**Positive**:
- Admins retain full oversight for audit and correction purposes.
- Regular users cannot accidentally view or overwrite colleagues' contacts.
- The ownership model is simple (one FK) and does not require a separate
  permissions table for Phase 1.

**Negative**:
- Every contact query for non-admin users must include a `WHERE owner = ?`
  clause; missing this filter is a data-leak bug and must be covered by tests.
- Role assignment and user provisioning (how users get the `admin` role) must
  be defined before the first deployment; this is out of scope for Phase 1 UI
  but must be handled in the backend.
