# ADR-007: Contact List Search & Filter — Name Search + City/Country Dropdowns

**Date**: 2026-05-22
**Status**: Accepted

## Context

The contact list page needs a way for users to locate specific contacts
efficiently. Three options were considered: name-only text search, full-text
search across all fields, or a combined name search with discrete filter
dropdowns for geographic fields.

## Decision

The contact list provides:

1. **Name text search**: A free-text input that filters contacts whose name
   contains the typed string (case-insensitive).
2. **City filter dropdown**: A dropdown populated with distinct city values
   from the user's visible contacts.
3. **Country filter dropdown**: A dropdown populated with distinct country
   values from the user's visible contacts.

Filters combine with AND logic: a contact must match the name search AND the
selected city AND the selected country to appear in results. Leaving a filter
blank means "any value".

## Consequences

**Positive**:
- City and country dropdowns provide a structured, low-effort way to find
  contacts in a specific location without needing to know exact spelling.
- Combined AND filtering allows precise narrowing (e.g., "all Smiths in London,
  UK") without a complex query builder.
- Dropdown values are derived from existing data, so no separate reference
  tables are needed.

**Negative**:
- Dropdown values must be refreshed when contacts are added or their city/country
  fields are updated; stale dropdowns could show removed values temporarily.
- Full-text search across address fields (street, postal code, phone) is not
  supported in Phase 1; users cannot search by street address.
