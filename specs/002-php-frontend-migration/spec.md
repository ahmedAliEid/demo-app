# Feature Specification: PHP Frontend Migration — Replace WordPress with Laravel

**Feature Branch**: `002-php-frontend-migration`

**Created**: 2026-05-24

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Access All Existing Pages Without Disruption (Priority: P1)

Any user who could previously access the application's pages (login, contacts list,
contact detail, address management) must be able to access the equivalent pages in
the new system at the same or equivalent URLs, with the same look-and-feel and data.

**Why this priority**: Ensures no regression in end-user experience. This is the
primary acceptance gate for the migration.

**Independent Test**: Navigate to every previously available page, perform every
previously available action (log in, view contacts, add/edit/delete contact), and
confirm the result matches the pre-migration behaviour.

**Acceptance Scenarios**:

1. **Given** a registered user, **When** they navigate to the login page, **Then** the login form renders and accepts credentials successfully.
2. **Given** an authenticated user, **When** they navigate to the contacts list, **Then** all contacts retrieved from the backend API are displayed correctly.
3. **Given** an authenticated user, **When** they add, edit, or delete a contact, **Then** the change is reflected immediately and persisted via the backend API.
4. **Given** an unauthenticated user, **When** they attempt to access a protected page, **Then** they are redirected to the login page.

---

### User Story 2 - WordPress Removal Leaves No Dead Code or Config (Priority: P2)

After migration, no WordPress-specific files, configuration, database tables, Docker
services, or environment variables remain in the repository or deployed environment.

**Why this priority**: Removes maintenance burden, security surface, and cognitive
overhead from the codebase.

**Independent Test**: Grep the repository for WordPress-specific identifiers (`wp_`, 
`wordpress`, `WP_`, `functions.php`, `wp-config`) and confirm zero matches in active
source files.

**Acceptance Scenarios**:

1. **Given** the migrated codebase, **When** a developer searches for WordPress-specific code, **Then** no WordPress PHP functions, hooks, or template patterns are found.
2. **Given** the Docker Compose file, **When** it is inspected, **Then** no `wordpress` or `mysql` services exist, the volumes `mysql_data` and `wordpress_data` are removed, and a PHP-FPM + Nginx service is present on port 8888 with a `backend` health-check dependency.
3. **Given** the CI pipeline, **When** it runs, **Then** no WordPress-specific steps (WP-CLI, WordPress unit test scaffolding) remain.

---

### User Story 3 - Developer Onboarding Uses Standard PHP Tooling (Priority: P3)

A developer unfamiliar with WordPress but familiar with modern PHP frameworks can
set up, run, and extend the frontend in under 30 minutes using standard PHP tooling
(Composer, `php artisan`, Nginx/PHP-FPM).

**Why this priority**: Improves developer experience and reduces onboarding friction
after removing WordPress-specific knowledge requirements.

**Independent Test**: Follow the README setup instructions from a clean checkout;
confirm the app runs locally and all pages load without additional manual steps.

**Acceptance Scenarios**:

1. **Given** a clean checkout, **When** a developer runs `composer install` and the documented start command, **Then** the frontend application starts without errors.
2. **Given** the running application, **When** a developer adds a new route and Blade view, **Then** it appears in the browser without additional configuration.

---

### Edge Cases

- What happens when the Spring Boot API is unavailable? The PHP frontend must render
  an informative error page rather than a raw exception or blank screen.
- What happens when a user's session expires mid-browsing? They are redirected to
  the login page with a clear "session expired" message.
- What happens when a page URL from the old WordPress site no longer exists? A
  404 page with a helpful message is returned (no uncaught exceptions).
- What happens when form input contains special characters or very long strings?
  Input is validated and sanitised before being forwarded to the backend API.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST render all existing UI pages (login, contacts list,
  contact detail, address management) using the new PHP framework.
- **FR-002**: The system MUST authenticate users by forwarding credentials to the
  Spring Boot REST API and managing the resulting session or token on the server side.
- **FR-003**: The system MUST protect all non-public routes; unauthenticated requests
  MUST be redirected to the login page.
- **FR-004**: The system MUST consume the existing Spring Boot REST API for all data
  operations (create, read, update, delete contacts and addresses).
- **FR-005**: The system MUST handle Spring Boot API errors gracefully and display
  user-friendly error messages without exposing internal stack traces.
- **FR-006**: All HTML output MUST be escaped by default to prevent cross-site scripting.
- **FR-007**: All state-changing form submissions MUST include CSRF protection.
- **FR-008**: The system MUST remove all WordPress-specific files, dependencies,
  and environment variables from the codebase. In the Docker Compose configuration
  this specifically means: the `wordpress` service, the `mysql` service (used solely
  by WordPress), and the named volumes `mysql_data` and `wordpress_data` must all
  be deleted.
- **FR-009**: The Docker Compose configuration MUST be updated to run the PHP
  frontend via PHP-FPM and Nginx (or equivalent), replacing the `wordpress`
  container. The new service MUST: (a) be reachable on the same host port previously
  used by the `wordpress` service (8888), (b) receive the backend API base URL via
  an environment variable (equivalent to the existing `DEMO_CONTACTS_API_URL`),
  and (c) declare a health-check dependency on the `backend` service so it only
  starts after the API is confirmed healthy.
- **FR-010**: The system MUST expose a health-check endpoint returning HTTP 200 that
  confirms the PHP application is running.

### Key Entities

- **Session**: Represents an authenticated user's server-side session; stores the
  backend API token and user identity; expires after the configured inactivity period.
- **Contact** (read from API): Display entity with name, email, phone, and associated
  addresses; owned by the Spring Boot layer; PHP layer renders only.
- **Address** (read from API): Sub-entity of Contact; owned by the Spring Boot layer.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All pages that existed in the WordPress frontend load successfully in
  the new PHP frontend with equivalent functionality and no visual regressions.
- **SC-002**: Zero WordPress-specific identifiers (`wp_`, `WP_`, `add_action`,
  `functions.php`) remain in active source files after migration.
- **SC-003**: A developer can complete local setup from a clean checkout in under
  30 minutes following the updated README.
- **SC-004**: Every protected page returns HTTP 302 to the login page for
  unauthenticated requests (100% of cases, verified by automated test suite).
- **SC-005**: All form submissions are protected by CSRF tokens; any request missing
  a valid token returns HTTP 419 (or equivalent rejection) — verified by test.
- **SC-006**: Spring Boot API error responses are translated into user-readable
  messages in 100% of tested error scenarios (4xx and 5xx).

## Assumptions

- The Spring Boot REST API endpoints and contracts remain unchanged during this
  migration; no backend changes are required.
- The existing UI design (layout, colour scheme, component structure) is preserved
  as-is; this migration is a technology swap, not a redesign.
- The PHP frontend runs behind Nginx as a reverse proxy; direct PHP-FPM exposure
  to the internet is not supported.
- Session storage uses the filesystem or a Redis-backed store available in the
  Docker Compose environment; no external session service needs to be provisioned.
- The current Docker Compose file (at time of spec creation) contains four services:
  `postgres` (Spring Boot DB — stays), `backend` (Spring Boot API — stays),
  `mysql` (WordPress DB — removed), `wordpress` (WordPress frontend — replaced).
  The migration is net-zero in service count: two services are removed, one new
  PHP-FPM + Nginx service is added.
- The migration covers only the Presentation layer; Kafka (Phase 2) and Spring Boot
  (API layer) are out of scope for this feature.
- End-to-end browser testing is in scope for verifying the golden path; exhaustive
  visual regression testing is out of scope for this iteration.
