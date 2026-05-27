# Feature Specification: Login & Contact Address Management

**Feature Branch**: `001-wordpress-java-kafka`

**Created**: 2026-05-22

**Status**: Draft

## Clarifications

### Session 2026-05-22

- Q: Do all authenticated users share one contact list, or is access role-based? → A: Role-based — admin users see and manage all contacts; regular users see and manage only their own contacts.
- Q: What happens if a duplicate contact name is submitted? → A: Block the submission — a validation error is shown if a contact with the same name already exists (within the user's visible scope).
- Q: How long before a session expires? → A: 30 minutes of inactivity; user is redirected to the login page with a message on expiry.
- Q: What is the search/filter scope on the contact list? → A: Name text search plus separate city and country filter dropdowns.

**Input**: User description: "simple project to list with login page and add, get, update contact address"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Secure Login (Priority: P1)

A registered user visits the application and logs in with their username and password.
Upon successful authentication they are redirected to the contact list page. An invalid
credential attempt shows an error message without revealing which field was wrong.

**Why this priority**: The entire application is behind authentication; nothing else works
without it.

**Independent Test**: Navigate to the app, enter valid credentials, and confirm the
contact list page loads. Enter invalid credentials and confirm a generic error message
appears with no redirect.

**Acceptance Scenarios**:

1. **Given** the user is on the login page, **When** they submit valid credentials,
   **Then** they are redirected to the contact list and a session is established.
2. **Given** the user submits invalid credentials, **When** the form is submitted,
   **Then** a generic error ("Invalid username or password") is shown and no session
   is created.
3. **Given** an authenticated session expires after 30 minutes of inactivity,
   **When** the user accesses a protected page, **Then** they are redirected to the
   login page with a message: "Your session has expired. Please log in again."

---

### User Story 2 — View Contact List (Priority: P2)

An authenticated user sees a paginated list of all contact addresses. Each row shows
the contact's name and primary address. The list is searchable by name.

**Why this priority**: Listing contacts is the primary landing experience after login
and the entry point for all other operations.

**Independent Test**: Log in and confirm a table/list of contacts is displayed. Add a
known contact and confirm it appears. Search by name and confirm filtered results.

**Acceptance Scenarios**:

1. **Given** the user is authenticated, **When** they visit the contacts page,
   **Then** a list of all contacts is shown with name and address visible.
2. **Given** there are no contacts, **When** the user visits the contacts page,
   **Then** an empty-state message is shown (e.g., "No contacts yet. Add one!").
3. **Given** contacts exist, **When** the user types in the name search field,
   **Then** the list filters to show only contacts whose name matches.
4. **Given** contacts exist, **When** the user selects a city or country from the
   filter dropdowns, **Then** the list narrows to contacts matching both the name
   search and the selected filter values.

---

### User Story 3 — Add Contact Address (Priority: P3)

An authenticated user fills in a form to add a new contact with a name and address
details. On success the new contact appears in the list immediately.

**Why this priority**: Core data-entry capability; required before Get or Update make
sense.

**Independent Test**: Open the Add Contact form, fill in all required fields, submit,
and verify the new contact appears in the list.

**Acceptance Scenarios**:

1. **Given** the user opens the Add Contact form, **When** they submit valid data,
   **Then** the contact is saved and displayed in the contact list.
2. **Given** the user submits the form with a missing required field, **When** the
   form is submitted, **Then** a field-level validation error is shown and no contact
   is created.
3. **Given** an address with all optional sub-fields, **When** submitted,
   **Then** all fields are stored and retrievable.

---

### User Story 4 — View Contact Detail (Priority: P3)

An authenticated user clicks a contact in the list to view its full address details on
a dedicated detail page or panel.

**Why this priority**: Required for reviewing a contact before editing.

**Independent Test**: Click an existing contact and confirm all stored address fields
are displayed.

**Acceptance Scenarios**:

1. **Given** the user clicks a contact, **When** the detail view opens,
   **Then** all address fields (street, city, country, postal code, phone) are shown.
2. **Given** an optional field was left blank on creation, **When** the detail view
   opens, **Then** the blank field is either hidden or shown as "—".

---

### User Story 5 — Update Contact Address (Priority: P4)

An authenticated user edits an existing contact's address fields and saves the changes.
The updated data is immediately reflected in the list and detail view.

**Why this priority**: Keeps contact data accurate over time.

**Independent Test**: Open an existing contact's edit form, change at least one field,
save, and confirm the change is reflected in both the list and detail view.

**Acceptance Scenarios**:

1. **Given** the user opens the edit form for a contact, **When** they modify a field
   and save, **Then** the updated value is shown in the list and detail view.
2. **Given** the user clears a required field and saves, **When** the form is
   submitted, **Then** a validation error is shown and the original data is preserved.
3. **Given** two users edit the same contact concurrently, **When** both save,
   **Then** the last write wins and no data is silently lost.

---

### Edge Cases

- Session expires after 30 minutes of inactivity. If the session expires mid-form-edit, the user is redirected to login with an expiry message; unsaved form data is lost.
- How does the system behave when the contact list grows beyond 1,000 entries?
- Duplicate contact names are blocked: a validation error is returned if a contact with the same name already exists within the user's visible scope.
- How are XSS payloads in name/address fields handled?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST require authentication before any contact data is accessible.
- **FR-002**: System MUST authenticate users via username and password.
- **FR-002a**: System MUST support two roles: `admin` and `user`.
- **FR-003**: System MUST display contacts to authenticated users based on role: admins
  see all contacts; regular users see only contacts they created.
- **FR-004**: System MUST allow authenticated users to add a new contact with name and
  address fields (street, city, state/province, postal code, country, phone number).
- **FR-005**: System MUST allow authenticated users to retrieve the full details of any
  contact.
- **FR-006**: System MUST allow authenticated users to update any field of a contact
  they own; admins may update any contact regardless of ownership.
- **FR-007**: System MUST validate required fields (at minimum: name, street, city,
  country) on add and update operations and display field-level error messages.
- **FR-007a**: System MUST reject a new contact whose name exactly matches an existing
  contact within the authenticated user's visible scope, returning a validation error.
- **FR-008**: System MUST sanitise all user input to prevent injection attacks.
- **FR-009**: System MUST redirect unauthenticated requests to the login page.
- **FR-009a**: Sessions MUST expire after 30 minutes of inactivity; the user MUST be
  redirected to the login page with an expiry notification on next access.
- **FR-010**: System MUST support a name text search on the contact list page.
- **FR-010a**: System MUST provide separate filter dropdowns for city and country on
  the contact list page; filters combine with the name search (AND logic).

### Key Entities

- **User**: Represents an authenticated system user. Attributes: username, hashed
  password, role (admin | user). Admins see and manage all contacts; regular users
  see and manage only contacts they created.
- **Contact**: The person or organisation associated with an address. Attributes: id,
  full name, owner (the user who created it), created date, updated date.
- **Address**: The postal/contact address belonging to a Contact. Attributes: street
  line 1, street line 2 (optional), city, state/province (optional), postal code,
  country, phone number (optional).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user can log in and reach the contact list in under 3 seconds on a
  standard broadband connection.
- **SC-002**: A user can add a new contact with all required fields in under 60 seconds
  from opening the form to seeing it in the list.
- **SC-003**: The contact list displays up to 100 entries without requiring the user
  to scroll excessively (pagination or virtual scroll applied).
- **SC-004**: 100% of invalid form submissions are caught client-side before reaching
  the server, with clear field-level error messages.
- **SC-005**: An unauthenticated request to any protected route is redirected to the
  login page within 1 second.
- **SC-006**: Updated contact data is visible in the list and detail view immediately
  after saving (no manual page refresh required).

## Assumptions

- Only internal/admin users will access this system; no public self-registration is
  required for Phase 1.
- Each contact has exactly one address; multiple addresses per contact are out of scope
  for Phase 1.
- The application is a web-based interface; mobile-native apps are out of scope.
- Soft-delete (archive) of contacts is out of scope; hard-delete may be added later.
- Pagination defaults to 20 contacts per page; this can be configured later.
- The WordPress front-end will consume the Spring Boot REST API for all contact
  operations; WordPress itself stores no contact data.
