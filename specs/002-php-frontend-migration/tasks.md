# Tasks: PHP Frontend Migration — Replace WordPress with Laravel

**Status**: Ready for implementation

**Organization**: Tasks are grouped by priority user story to enable independent implementation and testing.

**Format**: `[ID] [P?] [Story] Description`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization, Docker, and Nginx configuration

- [X] T001 Create `frontend/` directory scaffolding with Laravel 11 via `composer create-project laravel/laravel . --prefer-dist` in `frontend/`
- [X] T002 Configure `.env.example` with all required variables: `APP_KEY`, `APP_URL`, `APP_ENV`, `BACKEND_URL`, `SESSION_LIFETIME`, `LOG_CHANNEL` in `frontend/.env.example`
- [X] T003 Write `frontend/Dockerfile` based on `php:8.2-fpm` with curl/mbstring/xml extensions, `composer install --no-dev --optimize-autoloader`, `chown storage/ bootstrap/cache/`, `USER www-data`, `expose_php=Off`, `display_errors=Off`
- [X] T004 [P] Write `nginx/default.conf` with security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `server_tokens off`), PHP-FPM passthrough to `frontend:9000`, deny `.ht` access
- [X] T005 Update `docker-compose.yml`: add `frontend` + `nginx` services, remove `wordpress` + `mysql` services and their volumes

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core services, middleware, and config that MUST be complete before any user story

**Critical**: No user story work can begin until this phase is complete

- [X] T006 Implement `app/Services/ApiClient.php` with all seven public methods (`login`, `logout`, `listContacts`, `getContact`, `createContact`, `updateContact`, `getFilterOptions`) using `Http::withToken(session('token'))->timeout(5)->retry(3, 500)`; handle 401 (clear session, `UnauthenticatedException`), 409 (conflict response), 5xx (`ApiUnavailableException`); base URL from `config('services.backend.url')`
- [X] T007 Implement `app/Http/Middleware/Authenticate.php` — check `session('token')`, redirect to `route('login')` with intended URL if missing
- [X] T008 Create `config/services.php` with `'backend' => ['url' => env('BACKEND_URL', 'http://localhost:8080')]` — all `ApiClient` base URL reads from config only
- [X] T009 Create `app/Http/Requests/LoginRequest.php` — `required|string|max:100` on `username` and `password`
- [X] T010 Create `app/Http/Requests/ContactRequest.php` — `fullName` (required, max:255), `streetLine1` (required, max:255), `city` (required, max:100), `country` (required, max:100); all other fields optional string max:255
- [X] T011 Define all routes in `routes/web.php`: login (GET/POST), logout (POST), contacts CRUD (GET/POST/PUT), `/health` closure, throttle `POST /login` (6,1), wrap contact routes in `auth` middleware group

**Checkpoint**: Foundation ready — user story implementation can begin

---

## Phase 3: User Story 1 — Access All Existing Pages (Priority: P1)

**Goal**: Users can access all four pages (login, contacts list, contact detail, contact form) via Laravel with identical functionality and CSS classes

**Independent Test**: Navigate to `/login`, authenticate, view contacts list, view contact detail, create a contact, edit a contact, logout — all pages render correctly

### Tests for User Story 1

- [X] T012 [P] [US1] Write `tests/Unit/Services/ApiClientTest.php` — `Http::fake()` for all seven endpoints; assert URLs, headers, timeout, retry; assert 401 clears session; assert 5xx throws `ApiUnavailableException`
- [X] T013 [P] [US1] Write `tests/Feature/Auth/LoginTest.php` — `GET /login` returns 200; `POST /login` valid creds stores session + regenerates session ID + redirects; invalid creds redirects back with error; CSRF missing → 419; 7th request in 60s → 429
- [X] T014 [P] [US1] Write `tests/Feature/Auth/LogoutTest.php` — `POST /logout` clears session + redirects to `/login`; unauthenticated logout redirects to login
- [X] T015 [P] [US1] Write `tests/Feature/Contacts/ContactListTest.php` — authenticated `GET /contacts` returns 200; unauthenticated returns 302 to `/login`; filter params forwarded to `ApiClient`
- [X] T016 [P] [US1] Write `tests/Feature/Contacts/ContactDetailTest.php` — `GET /contacts/{id}` returns 200; 404 from API renders error page
- [X] T017 [P] [US1] Write `tests/Feature/Contacts/ContactFormTest.php` — `POST /contacts` missing fields returns 422; valid POST calls `ApiClient::createContact` and redirects; CSRF missing → 419
- [X] T018 [P] [US1] Write `tests/Feature/HealthTest.php` — `GET /health` returns 200 without authentication

### Implementation for User Story 1

- [X] T019 [US1] Implement `app/Http/Controllers/AuthController::showLogin` — return `view('auth.login')`, redirect to `/contacts` if already authenticated
- [X] T020 [US1] Implement `app/Http/Controllers/AuthController::login` — validate via `LoginRequest`, call `ApiClient::login()`, store token/role in session, `$request->session()->regenerate()`, redirect to intended or `/contacts`
- [X] T021 [US1] Implement `app/Http/Controllers/AuthController::logout` — call `ApiClient::logout()`, `$request->session()->invalidate()`, `$request->session()->regenerateToken()`, redirect to `/login`
- [X] T022 [P] [US1] Implement `app/Http/Controllers/ContactController::index` — call `ApiClient::listContacts()` + `getFilterOptions()`, pass to `contacts.index` view
- [X] T023 [P] [US1] Implement `app/Http/Controllers/ContactController::show` — call `ApiClient::getContact($id)`, pass to `contacts.show` view
- [X] T024 [P] [US1] Implement `app/Http/Controllers/ContactController::create` — return `view('contacts.form', ['contact' => null, 'action' => route('contacts.store')])`
- [X] T025 [US1] Implement `app/Http/Controllers/ContactController::store` — validate via `ContactRequest`, call `ApiClient::createContact()`, redirect to `/contacts` on success, back with errors on 4xx
- [X] T026 [P] [US1] Implement `app/Http/Controllers/ContactController::edit` — call `ApiClient::getContact($id)`, pass to `contacts.form` with PUT action
- [X] T027 [US1] Implement `app/Http/Controllers/ContactController::update` — validate via `ContactRequest`, call `ApiClient::updateContact()`, redirect to `/contacts/{id}` on success
- [X] T028 [US1] Create `resources/views/layouts/app.blade.php` — shared layout with header nav (logo, logout link, role badge), `@yield('content')`, CSS identical to WordPress (`demo-contacts-wrap`, `demo-login-form`, `demo-notice-error`, `demo-notice-success`)
- [X] T029 [US1] Create `resources/views/auth/login.blade.php` — form with `@csrf`, username/password inputs, `@error` directives, `demo-login-form` CSS class
- [X] T030 [US1] Create `resources/views/contacts/index.blade.php` — paginated table with search and city/country filter dropdowns (`data-autosubmit`), Add Contact button, `@foreach` rows
- [X] T031 [US1] Create `resources/views/contacts/show.blade.php` — contact detail card with all fields, Edit button (admin/own contact), Back to List link
- [X] T032 [US1] Create `resources/views/contacts/form.blade.php` — shared create/edit form, `@csrf`, `@method('PUT')` when editing, `@error` inline validation
- [X] T033 [P] [US1] Create `resources/views/errors/404.blade.php` — styled error page using `demo-notice-error`, no stack trace
- [X] T034 [P] [US1] Create `resources/views/errors/500.blade.php` — styled error page using `demo-notice-error`, "API unavailable" message, no raw exceptions
- [X] T035 [P] [US1] Copy CSS from `contacts-child/style.css` to `resources/css/app.css`, publish to `public/css/app.css`
- [X] T036 [P] [US1] Copy filter auto-submit JS from `contacts.js` to `resources/js/app.js`, attach to `[data-autosubmit]` selectors

**Checkpoint**: All four pages functional — login, logout, contacts list, contact detail, create/edit contact — tested independently

---

## Phase 4: User Story 2 — WordPress Removal (Priority: P2)

**Goal**: No WordPress-specific files, config, Docker services, or env vars remain in the repository

**Independent Test**: Grep for `wp_`, `WP_`, `add_action`, `functions.php`, `wp-config`, `wordpress` — zero matches in active source files

### Implementation for User Story 2

- [X] T037 [US2] Delete `wordpress/` directory entirely (`rm -rf wordpress/`)
- [X] T038 [US2] Remove `mysql` and `wordpress` services from `docker-compose.yml`; remove `mysql_data` and `wordpress_data` volumes; also remove nginx `frontend + nginx` addition was done in T005, verify no residual WordPress references
- [X] T039 [US2] Update `.env.example`: remove `WORDPRESS_*` and `MYSQL_*` variables; add `SESSION_SECURE_COOKIE=false`, `SESSION_SAME_SITE=lax`, `SESSION_HTTP_ONLY=true`
- [X] T040 [US2] Run verification grep: `grep -r "wp_\|WP_\|add_action\|functions.php\|wp-config\|wordpress" --include="*.php" --include="*.yml" --include="*.env*" .` — must return zero matches in active source files

**Checkpoint**: WordPress fully excised — zero identifiers remain

---

## Phase 5: User Story 3 — Developer Onboarding (Priority: P3)

**Goal**: A developer familiar with PHP (not WordPress) can set up and run the frontend in under 30 minutes

**Independent Test**: Follow README from a clean checkout — `composer install`, `docker compose up -d`, all pages load

### Implementation for User Story 3

- [X] T041 [US3] Ensure `frontend/.env.example` has complete documentation comments for all variables
- [X] T042 [US3] Update `docs/GUIDE.md` — update version date, replace WordPress setup instructions with Laravel/Nginx/PHP-FPM instructions
- [X] T043 [US3] Update `docs/memory/ARCHITECTURE.md` — update component list to replace WordPress with Laravel frontend service
- [X] T044 [US3] Update `docs/memory/DECISIONS.md` — add ADR-008 entry as Active, mark ADR-001/ADR-002 as Superseded with cross-reference
- [X] T045 [US3] Update `docs/memory/PROJECT_CONTEXT.md` — review and update product constraints and priorities
- [X] T046 [US3] Update `docs/memory/INDEX.md` — add pointers to updated memory files

**Checkpoint**: Developer can onboard with standard PHP tooling — no WordPress knowledge required

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validation, security hardening, and final verification

- [X] T047 Run `php artisan test` — confirm all test suites pass (Phase 3 test tasks)
- [X] T048 Run `composer audit` — confirm no critical vulnerabilities
- [X] T049 Verify `docker compose up -d` starts healthy on a clean checkout (no `wordpress` or `mysql` required)
- [X] T050 Manual smoke test: login → contacts list → contact detail → create contact → edit → logout
- [X] T051 Verify `GET /health` returns 200
- [X] T052 Verify no P0 violations via architecture-guard review
- [X] T053 Run final WordPress grep scan — confirm zero matches

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories
- **Phase 3 (US1 — P1)**: Depends on Phase 2 — core page delivery
- **Phase 4 (US2 — P2)**: Depends on Phase 1 (docker-compose setup) — can run in parallel with Phase 3 at file level but sequential recommended to avoid docker-compose conflicts
- **Phase 5 (US3 — P3)**: Depends on Phase 3 (frontend exists) and Phase 4 (WordPress removed)
- **Phase 6 (Polish)**: Depends on Phases 3, 4, 5 completion

### User Story Dependencies

- **US1 (P1)**: Can start after Phase 2 — no dependencies on other stories
- **US2 (P2)**: Can start after Phase 1 T005 — minimal dependency on US1
- **US3 (P3)**: Depends on US1 and US2 completion (docs must reflect final state)

### Within Each User Story

- Tests MUST be written and fail before implementation
- Controllers before views
- Views before assets (CSS/JS)
- Story complete before moving to next

### Parallel Opportunities

- T004 and T001-T003/T005 can run in parallel (different files)
- T012-T018 (all US1 tests) can run in parallel
- T019-T021 (AuthController) sequential
- T022-T027 (ContactController) — T022/T023/T024/T026 parallel, T025/T027 depend on Form Requests
- T033-T036 (error pages, CSS, JS) can run in parallel

---

## Implementation Strategy

### MVP Scope (US1 Only)
1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: US1 — All pages functional
4. **STOP and VALIDATE**: All four pages work, tests pass
5. Deploy/demo if ready

### Incremental Delivery
1. Setup + Foundational → Foundation ready
2. Add US1 → All pages working → Deploy/Demo
3. Add US2 → WordPress removed → Deploy/Demo
4. Add US3 + Polish → Complete migration → Deploy/Demo

---

## Notes

- All PHP files must use `declare(strict_types=1)`
- Controllers must NOT call `Http` directly — only via `ApiClient` (P0-05)
- No Eloquent/DB/PDO in PHP frontend (P0-06)
- JWT token stored in server-side session only — never exposed to browser
- All Blade forms must include `@csrf`
- CSS class names must match WordPress originals exactly
- Commit after each logical task group
