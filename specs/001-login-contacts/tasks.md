---
description: "Task list for Login & Contact Address Management — optimised for minimal-context execution"
---

# Tasks: Login & Contact Address Management

**Input**: Design documents from `specs/001-login-contacts/`

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/openapi.yaml ✅

**Implementation model note**: Every task references exactly ONE file and names the
exact class / method / content to produce. Each task is independently completable by
reading only the referenced source document plus this file.

**Tests**: Not included (not requested in spec). Add `/speckit-tasks --tdd` to generate
test tasks.

**Organisation**: Tasks are grouped by user story for independent delivery.

## Format: `[ID] [P?] [Story?] Description — file path`

- **[P]**: No dependencies on incomplete sibling tasks; safe to run in parallel
- **[Story]**: Maps to user story in spec.md (US1–US5)
- Every task names the exact file path to create or edit

---

## Phase 1: Setup (Project Scaffolding)

**Purpose**: Create the skeleton both layers need before any feature work begins.

- [x] T001 Create `backend/pom.xml` — Maven project with dependencies: `spring-boot-starter-web`, `spring-boot-starter-security`, `spring-boot-starter-data-jpa`, `spring-boot-starter-validation`, `springdoc-openapi-starter-webmvc-ui:2.5.0`, `jjwt-api:0.12.6` + `jjwt-impl` + `jjwt-jackson`, `flyway-core`, `postgresql`, `testcontainers-bom:1.19.8`, `testcontainers:postgresql`, `spring-security-test`; Java 21 compiler plugin; main class `com.demo.app.DemoAppApplication`
- [x] T002 [P] Create `backend/src/main/java/com/demo/app/DemoAppApplication.java` — standard `@SpringBootApplication` entry point in package `com.demo.app`
- [x] T003 [P] Create `backend/src/main/resources/application.yml` — configure `spring.datasource` (reads `${DB_URL}`, `${DB_USERNAME}`, `${DB_PASSWORD}`), `spring.jpa.hibernate.ddl-auto: validate`, `spring.flyway.enabled: true`, `app.jwt.secret: ${JWT_SECRET}`, `app.jwt.expiry-seconds: ${JWT_EXPIRY_SECONDS:1800}`, `management.endpoints.web.exposure.include: health,prometheus`
- [x] T004 [P] Create `backend/src/main/resources/application-test.yml` — override datasource to H2 in-memory (`jdbc:h2:mem:testdb`), disable Flyway (`spring.flyway.enabled: false`), set `spring.jpa.hibernate.ddl-auto: create-drop`
- [x] T005 [P] Create `backend/src/main/resources/db/migration/V1__create_users.sql` — exact SQL from `data-model.md` § V1__create_users.sql
- [x] T006 [P] Create `backend/src/main/resources/db/migration/V2__create_contacts.sql` — exact SQL from `data-model.md` § V2__create_contacts.sql (include indexes)
- [x] T007 [P] Create `backend/src/main/resources/db/migration/V3__create_addresses.sql` — exact SQL from `data-model.md` § V3__create_addresses.sql (include indexes)
- [x] T008 [P] Create `docker-compose.yml` at repo root — `postgres:15-alpine` service on port 5432; env vars `POSTGRES_DB=demo_contacts`, `POSTGRES_USER`, `POSTGRES_PASSWORD`; named volume `postgres_data`
- [x] T009 [P] Create `wordpress/composer.json` — require `php: ^8.1`; autoload PSR-4 `DemoApp\\`: `wp-content/plugins/demo-contacts-api/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Cross-cutting infrastructure every user story depends on. No story work
starts until this phase is complete.

**⚠️ CRITICAL**: Complete T010–T027 before any Phase 3+ task.

- [x] T010 Create `backend/src/main/java/com/demo/app/domain/Role.java` — Java `enum` with values `ADMIN`, `USER` in package `com.demo.app.domain`
- [x] T011 [P] Create `backend/src/main/java/com/demo/app/domain/User.java` — `@Entity @Table(name="users")` implementing `UserDetails`; fields: `id` (`@Id @GeneratedValue BIGSERIAL`), `username` (`@Column(unique=true, nullable=false, length=100)`), `passwordHash` (`@Column(name="password_hash", nullable=false, length=255)`), `role` (`@Enumerated(EnumType.STRING) Role`), `createdAt`, `updatedAt`; `UserDetails` methods delegate to `role`; `getAuthorities()` returns `ROLE_ADMIN` or `ROLE_USER`
- [x] T012 [P] Create `backend/src/main/java/com/demo/app/domain/Contact.java` — `@Entity @Table(name="contacts")`; fields: `id`, `fullName` (`@Column(name="full_name", nullable=false, length=255)`), `owner` (`@ManyToOne(fetch=LAZY) @JoinColumn(name="owner_id", nullable=false)`), `address` (`@OneToOne(mappedBy="contact", cascade=ALL, fetch=LAZY, orphanRemoval=true)`), `createdAt`, `updatedAt`
- [x] T013 [P] Create `backend/src/main/java/com/demo/app/domain/Address.java` — `@Entity @Table(name="addresses")`; fields: `id`, `contact` (`@OneToOne(fetch=LAZY) @JoinColumn(name="contact_id", nullable=false, unique=true)`), `streetLine1` (`nullable=false, length=255`), `streetLine2` (nullable), `city` (`nullable=false, length=100`), `stateProvince` (nullable), `postalCode` (`length=20`, nullable), `country` (`nullable=false, length=100`), `phone` (`length=50`, nullable)
- [x] T014 [P] Create `backend/src/main/java/com/demo/app/repository/UserRepository.java` — `JpaRepository<User, Long>`; add `Optional<User> findByUsername(String username)`
- [x] T015 [P] Create `backend/src/main/java/com/demo/app/security/UserDetailsServiceImpl.java` — `@Service implements UserDetailsService`; constructor-inject `UserRepository`; `loadUserByUsername` calls `userRepository.findByUsername(username).orElseThrow(() -> new UsernameNotFoundException(...))`
- [x] T016 [P] Create `backend/src/main/java/com/demo/app/security/JwtTokenProvider.java` — `@Component`; read `app.jwt.secret` and `app.jwt.expiry-seconds` from `@Value`; `generateToken(UserDetails)` → signs HS256 JWT with `sub=username`, `role`, `iat`, `exp=now+expirySeconds`; `validateToken(String)` → returns `true` if signature valid and not expired; `getUsername(String)` → extracts `sub` claim
- [x] T017 [P] Create `backend/src/main/java/com/demo/app/security/JwtAuthenticationFilter.java` — `extends OncePerRequestFilter`; extract `Authorization: Bearer <token>` header; if valid, build `UsernamePasswordAuthenticationToken` and set in `SecurityContextHolder`; on any failure log at DEBUG and continue the filter chain without throwing
- [x] T018 [P] Create `backend/src/main/java/com/demo/app/config/SecurityConfig.java` — `@Configuration @EnableMethodSecurity`; register `JwtAuthenticationFilter` before `UsernamePasswordAuthenticationFilter`; `permitAll()` only for `POST /api/v1/auth/login` and `GET /actuator/health`; all other requests require authentication; disable CSRF; stateless session; BCrypt password encoder bean
- [x] T019 [P] Create `backend/src/main/java/com/demo/app/exception/ContactNotFoundException.java` — `RuntimeException` subclass; constructor `(Long id)` sets message `"Contact not found: " + id`
- [x] T020 [P] Create `backend/src/main/java/com/demo/app/exception/DuplicateContactNameException.java` — `RuntimeException` subclass; constructor `(String name)` sets message `"A contact named '" + name + "' already exists"`
- [x] T021 [P] Create `backend/src/main/java/com/demo/app/exception/GlobalExceptionHandler.java` — `@RestControllerAdvice`; handle: `MethodArgumentNotValidException` → `422` with field errors map (`fieldName → defaultMessage`); `DuplicateContactNameException` → `409`; `ContactNotFoundException` → `404`; `AccessDeniedException` → `403`; all responses use RFC 7807 `ProblemDetail` (`ProblemDetail.forStatusAndDetail(...)`)
- [x] T022 [P] Create `backend/src/main/java/com/demo/app/service/ContactEventPublisher.java` — `interface` with methods `void contactCreated(Contact contact)` and `void contactUpdated(Contact contact)` in package `com.demo.app.service`
- [x] T023 [P] Create `backend/src/main/java/com/demo/app/service/NoOpContactEventPublisher.java` — `@Component implements ContactEventPublisher`; both methods are empty (Phase 2 Kafka implementation replaces this bean)
- [x] T024 [P] Create `backend/src/main/java/com/demo/app/config/OpenApiConfig.java` — `@Configuration`; define `OpenAPI` bean with title `"Demo Contacts API"`, version `"1.0.0"`, `BearerAuth` security scheme (`http`, `bearer`, `JWT`); set servers for `localhost:8080` and `api.demo-app.internal`
- [x] T025 [P] Create `backend/src/main/java/com/demo/app/dto/LoginRequest.java` — Java record with fields `@NotBlank String username`, `@NotBlank String password`
- [x] T026 [P] Create `backend/src/main/java/com/demo/app/dto/LoginResponse.java` — Java record with fields `String token`, `long expiresIn`, `String role`
- [x] T027 [P] Create `backend/src/main/java/com/demo/app/dto/PagedResponse.java` — generic Java record `PagedResponse<T>` with fields `List<T> content`, `int page`, `int size`, `long totalElements`, `int totalPages`

**Checkpoint**: All foundational classes exist — user story implementation can begin.

---

## Phase 3: User Story 1 — Secure Login (Priority: P1) 🎯 MVP

**Goal**: Users can log in with username/password and receive a JWT; invalid credentials
return a generic error; expired sessions redirect to login.

**Independent Test**: `POST /api/v1/auth/login` with valid credentials returns `200` +
JWT. Same endpoint with wrong password returns `401`. Any protected endpoint with
expired/missing token returns `401`.

### Implementation for User Story 1

- [x] T028 [US1] Create `backend/src/main/java/com/demo/app/service/AuthService.java` — `@Service`; constructor-inject `UserDetailsServiceImpl`, `JwtTokenProvider`, `PasswordEncoder`; method `LoginResponse login(LoginRequest req)`: load user by username (throw generic `BadCredentialsException` if not found to avoid username enumeration), verify password with `passwordEncoder.matches`, call `jwtTokenProvider.generateToken(user)`, return `LoginResponse(token, expirySeconds, role)`
- [x] T029 [US1] Create `backend/src/main/java/com/demo/app/controller/AuthController.java` — `@RestController @RequestMapping("/api/v1/auth")`; constructor-inject `AuthService`; `POST /login` calls `authService.login(req)` returns `200 LoginResponse`; `POST /logout` (authenticated) returns `204` (JWT is stateless — client discards token)

**Checkpoint**: US1 complete — login/logout independently functional. Test before proceeding.

---

## Phase 4: User Story 2 — View Contact List (Priority: P2)

**Goal**: Authenticated users see a paginated, filtered contact list scoped to their role.
Admins see all contacts; regular users see only their own.

**Independent Test**: Authenticated `GET /api/v1/contacts` returns `200` with
`PagedContactSummary`. Add `?name=jo` and confirm filtered results. Add `?city=London`
and confirm AND-combined filtering.

### Implementation for User Story 2

- [x] T030 [P] [US2] Create `backend/src/main/java/com/demo/app/repository/ContactRepository.java` — `JpaRepository<Contact, Long>` extending `JpaSpecificationExecutor<Contact>`; add: `boolean existsByFullNameIgnoreCaseAndOwner(String fullName, User owner)`, `boolean existsByFullNameIgnoreCase(String fullName)`, `@Query("SELECT DISTINCT a.city FROM Address a JOIN a.contact c WHERE (:ownerScope IS NULL OR c.owner = :ownerScope) ORDER BY a.city") List<String> findDistinctCities(@Param("ownerScope") User owner)`, same pattern for `findDistinctCountries`
- [x] T031 [P] [US2] Create `backend/src/main/java/com/demo/app/repository/ContactSpecifications.java` — static factory class; `Specification<Contact> hasNameLike(String name)` → `cb.like(cb.lower(root.get("fullName")), "%" + name.toLowerCase() + "%")`; `hasCity(String city)` → join to `address`, `cb.equal(cb.lower(addressJoin.get("city")), city.toLowerCase())`; `hasCountry(String country)` → same pattern; `ownedBy(User user)` → `cb.equal(root.get("owner"), user)`
- [x] T032 [P] [US2] Create `backend/src/main/java/com/demo/app/dto/ContactSummary.java` — Java record with fields `Long id`, `String fullName`, `String city`, `String country`; add static factory `ContactSummary.from(Contact c)` that reads `c.getAddress()` for city/country
- [x] T033 [P] [US2] Create `backend/src/main/java/com/demo/app/dto/FilterOptions.java` — Java record with fields `List<String> cities`, `List<String> countries`
- [x] T034 [US2] Create `backend/src/main/java/com/demo/app/service/ContactService.java` — `@Service`; constructor-inject `ContactRepository`, `UserRepository`, `ContactEventPublisher`; implement `PagedResponse<ContactSummary> listContacts(String name, String city, String country, int page, int size, Authentication auth)`: resolve current `User` from auth; build `Specification` — if admin use no owner filter, else add `ownedBy(user)`; add `hasNameLike` if `name` non-blank; add `hasCity` if non-blank; add `hasCountry` if non-blank; call `contactRepository.findAll(spec, PageRequest.of(page, size))`; map to `ContactSummary.from(c)`; return `PagedResponse`; implement `FilterOptions getFilterOptions(Authentication auth)`: resolve user; if admin pass `null` to repo methods (no scope filter), else pass user; return `new FilterOptions(cities, countries)`
- [x] T035 [US2] Create `backend/src/main/java/com/demo/app/controller/ContactController.java` — `@RestController @RequestMapping("/api/v1/contacts")`; constructor-inject `ContactService`; `GET /` → calls `contactService.listContacts(name, city, country, page, size, auth)` returns `200 PagedResponse<ContactSummary>`; `GET /filter-options` → calls `contactService.getFilterOptions(auth)` returns `200 FilterOptions`

**Checkpoint**: US2 complete — authenticated users can list and filter contacts.

---

## Phase 5: User Story 3 — Add Contact Address (Priority: P3)

**Goal**: Authenticated users can create a new contact. Duplicate names (within visible
scope) return `409`. Missing required fields return `422`.

**Independent Test**: `POST /api/v1/contacts` with valid body returns `201` + `Location`
header + `ContactResponse`. Same name twice returns `409`. Missing `country` returns `422`.

### Implementation for User Story 3

- [x] T036 [P] [US3] Create `backend/src/main/java/com/demo/app/dto/ContactRequest.java` — Java record with Bean Validation: `@NotBlank @Size(max=255) String fullName`, `@NotBlank @Size(max=255) String streetLine1`, `@Size(max=255) String streetLine2`, `@NotBlank @Size(max=100) String city`, `@Size(max=100) String stateProvince`, `@Size(max=20) String postalCode`, `@NotBlank @Size(max=100) String country`, `@Size(max=50) String phone`
- [x] T037 [P] [US3] Create `backend/src/main/java/com/demo/app/dto/ContactResponse.java` — Java record with all fields from `data-model.md` DTO Mapping § ContactResponse (`id`, `fullName`, `streetLine1`, `streetLine2`, `city`, `stateProvince`, `postalCode`, `country`, `phone`, `createdAt`, `updatedAt`); add static factory `ContactResponse.from(Contact c)`
- [x] T038 [US3] Add `createContact(ContactRequest req, Authentication auth)` to `backend/src/main/java/com/demo/app/service/ContactService.java` — resolve current `User`; if admin: check `contactRepository.existsByFullNameIgnoreCase(req.fullName())` → throw `DuplicateContactNameException` if true; else: check `contactRepository.existsByFullNameIgnoreCaseAndOwner(req.fullName(), user)` → throw if true; build `Contact` (set `owner=user`, `fullName`), build `Address` from req fields, set `contact.setAddress(address)`, save with `contactRepository.save(contact)`; call `contactEventPublisher.contactCreated(contact)`; return `ContactResponse.from(contact)`
- [x] T039 [US3] Add `POST /` endpoint to `backend/src/main/java/com/demo/app/controller/ContactController.java` — `@PostMapping`; `@RequestBody @Valid ContactRequest req`; call `contactService.createContact(req, auth)`; return `ResponseEntity.created(URI.create("/api/v1/contacts/" + response.id())).body(response)`

**Checkpoint**: US3 complete — contacts can be created with full validation.

---

## Phase 6: User Story 4 — View Contact Detail (Priority: P3)

**Goal**: Authenticated users can fetch the full detail of a single contact. Regular
users cannot access contacts they don't own (returns `403`).

**Independent Test**: `GET /api/v1/contacts/{id}` for owned contact returns `200`
`ContactResponse`. Same request by a different user returns `403`. Non-existent ID
returns `404`.

### Implementation for User Story 4

- [x] T040 [US4] Create `backend/src/main/java/com/demo/app/security/ContactSecurityService.java` — `@Service("contactSecurity")`; constructor-inject `ContactRepository`; `boolean isOwner(Long contactId, Authentication auth)`: load contact by id (return `false` if not found); return `contact.getOwner().getUsername().equals(auth.getName())`
- [x] T041 [US4] Add `getContact(Long id, Authentication auth)` to `backend/src/main/java/com/demo/app/service/ContactService.java` — load contact with `contactRepository.findById(id).orElseThrow(() -> new ContactNotFoundException(id))`; if user is not admin and `!contactSecurity.isOwner(id, auth)` throw `AccessDeniedException("Forbidden")`; return `ContactResponse.from(contact)`; also constructor-inject `ContactSecurityService`
- [x] T042 [US4] Add `GET /{id}` endpoint to `backend/src/main/java/com/demo/app/controller/ContactController.java` — `@GetMapping("/{id}")`; call `contactService.getContact(id, auth)`; return `200 ContactResponse`

**Checkpoint**: US4 complete — contact detail page can be rendered.

---

## Phase 7: User Story 5 — Update Contact Address (Priority: P4)

**Goal**: Authenticated users can update a contact they own. Admins can update any
contact. Duplicate name on update returns `409`. Clearing required fields returns `422`.

**Independent Test**: `PUT /api/v1/contacts/{id}` with changed `city` returns `200`
updated `ContactResponse`. Attempt by non-owner returns `403`. Clearing `fullName`
returns `422`.

### Implementation for User Story 5

- [x] T043 [US5] Add `updateContact(Long id, ContactRequest req, Authentication auth)` to `backend/src/main/java/com/demo/app/service/ContactService.java` — load contact (throw `ContactNotFoundException` if absent); check ownership via `contactSecurity.isOwner` (throw `AccessDeniedException` if non-admin and not owner); duplicate-name check: if name changed, apply same admin-vs-user scope logic as `createContact`; update `contact.setFullName`, update all `address` fields from req; set `contact.setUpdatedAt(Instant.now())`; save; call `contactEventPublisher.contactUpdated(contact)`; return `ContactResponse.from(contact)`
- [x] T044 [US5] Add `PUT /{id}` endpoint to `backend/src/main/java/com/demo/app/controller/ContactController.java` — `@PutMapping("/{id}")`; `@RequestBody @Valid ContactRequest req`; call `contactService.updateContact(id, req, auth)`; return `200 ContactResponse`

**Checkpoint**: US5 complete — all CRUD operations on contacts are functional.

---

## Phase 8: WordPress Layer (Presentation)

**Purpose**: Build the WordPress frontend that consumes the Spring Boot API. Requires
Phase 3–7 backend to be running locally (see `quickstart.md`).

### Plugin Infrastructure

- [x] T045 Create `wordpress/wp-content/plugins/demo-contacts-api/demo-contacts-api.php` — plugin header comment (`Plugin Name: Demo Contacts API`, `Version: 1.0.0`); define constant `DEMO_CONTACTS_API_URL` reading WP option `demo_contacts_api_url`; require `includes/class-api-client.php`, `includes/class-auth-handler.php`, `includes/class-contact-handler.php`; register activation hook; enqueue `assets/js/contacts.js` and `assets/css/contacts.css` via `wp_enqueue_scripts`
- [x] T046 [P] Create `wordpress/wp-content/plugins/demo-contacts-api/includes/class-api-client.php` — class `Demo_Contacts_API_Client`; private `string $base_url`; constructor reads `DEMO_CONTACTS_API_URL`; private `request(string $method, string $path, array $body = [], bool $auth = true): array|WP_Error` — builds `WP_Http` args with `Content-Type: application/json`, conditionally adds `Authorization: Bearer <token>` from `Demo_Contacts_Auth_Handler::get_token()`; calls `wp_remote_request`; on HTTP 401 clears session and returns `WP_Error`; decodes JSON response; public methods: `post(path, body, auth=true)`, `get(path, params=[], auth=true)`, `put(path, body)`
- [x] T047 [P] Create `wordpress/wp-content/plugins/demo-contacts-api/includes/class-auth-handler.php` — class `Demo_Contacts_Auth_Handler`; `static login(string $username, string $password): bool|WP_Error` — calls `$client->post('/api/v1/auth/login', compact('username','password'), false)`; on success stores JWT in `$_SESSION['demo_jwt']`, role in `$_SESSION['demo_role']`; returns `true`; `static logout()` — calls API logout, clears session keys; `static get_token(): string|null` — returns `$_SESSION['demo_jwt'] ?? null`; `static get_role(): string` — returns `$_SESSION['demo_role'] ?? 'user'`; `static is_authenticated(): bool` — returns `get_token() !== null`; `static require_auth()` — if not authenticated, `wp_redirect(home_url('/login'))` and `exit`
- [x] T048 [P] Create `wordpress/wp-content/plugins/demo-contacts-api/includes/class-contact-handler.php` — class `Demo_Contacts_Contact_Handler`; constructor-inject `Demo_Contacts_API_Client $client`; `list_contacts(array $filters = [], int $page = 0): array|WP_Error` — calls `$client->get('/api/v1/contacts', $filters + ['page'=>$page, 'size'=>20])`; `get_contact(int $id): array|WP_Error`; `create_contact(array $data): array|WP_Error` — calls `$client->post('/api/v1/contacts', $data)`; `update_contact(int $id, array $data): array|WP_Error` — calls `$client->put("/api/v1/contacts/$id", $data)`; `get_filter_options(): array|WP_Error` — calls `$client->get('/api/v1/contacts/filter-options')`

### Child Theme & Templates

- [x] T049 Create `wordpress/wp-content/themes/contacts-child/style.css` — theme header: `Theme Name: Contacts Child`, `Template: twentytwentyfour`; minimal CSS for contacts table, form layout, error/success notice, search bar, filter dropdowns; responsive breakpoint at 768px
- [x] T050 [P] Create `wordpress/wp-content/themes/contacts-child/functions.php` — enqueue parent theme stylesheet via `wp_enqueue_style`; start PHP session if not started (`session_start()`); register custom REST endpoint `GET /wp-json/demo/v1/ping` returning `['status'=>'ok']` for WP health check (constitution principle VI)
- [x] T051 [P] Create `wordpress/wp-content/themes/contacts-child/templates/login.html` — WP block template for the login page slug; renders a PHP partial via `get_template_part`; the partial (`parts/login-form.php`) outputs: nonce field (`wp_nonce_field('demo_login')`), username input, password input, submit button; on `POST` validates nonce with `wp_verify_nonce`, calls `Demo_Contacts_Auth_Handler::login()`, on success redirects to contacts page, on failure outputs error notice
- [x] T052 [P] Create `wordpress/wp-content/themes/contacts-child/templates/contacts-list.html` — WP block template; PHP partial calls `Demo_Contacts_Auth_Handler::require_auth()`; calls `get_filter_options()` to populate city/country `<select>` dropdowns; reads `$_GET['name']`, `$_GET['city']`, `$_GET['country']`, `$_GET['page']`; calls `list_contacts()` with sanitised values; renders table with columns: Name, City, Country, Actions (View/Edit links); renders pagination links; renders empty-state if `totalElements == 0`; all output escaped with `esc_html()`
- [x] T053 [P] Create `wordpress/wp-content/themes/contacts-child/templates/contact-detail.html` — WP block template; PHP partial: require auth; read `$_GET['id']` (cast to int, validate > 0); call `get_contact(id)`; on `WP_Error` display error notice; render all address fields (hide optional blank fields); render "Edit" button linking to `contact-form` page with `?id=<id>`; all values escaped with `esc_html()`
- [x] T054 Create `wordpress/wp-content/themes/contacts-child/templates/contact-form.html` — WP block template; PHP partial: require auth; if `$_GET['id']` present → update mode: call `get_contact(id)`, pre-fill form fields; else → create mode: empty fields; on `POST`: validate nonce, sanitise all inputs with `sanitize_text_field()`, call `create_contact()` or `update_contact()` accordingly; on success redirect to contacts list; on `WP_Error` or `409`/`422` response re-render form with inline error messages; required fields marked visually

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Observability, logging, and final validation across all stories.

- [x] T055 Edit `backend/src/main/resources/application.yml` — add Logback JSON config: `logging.structured.format.console: ecs` (Spring Boot 3.3 structured logging); set `logging.level.com.demo.app: INFO`; set `logging.level.org.springframework.security: WARN`
- [x] T056 [P] Edit `backend/src/main/java/com/demo/app/config/SecurityConfig.java` — expose `GET /actuator/health` and `GET /actuator/prometheus` as `permitAll()` in the security filter chain (already configured — verify these paths are present)
- [x] T057 [P] Create `backend/src/main/java/com/demo/app/config/TracingConfig.java` — `@Configuration`; import `micrometer-tracing-bridge-otel` (add to pom.xml); configure `ObservationRegistry` bean; ensures trace IDs appear in all log lines via MDC
- [x] T058 [P] Add `pom.xml` entry for `spring-boot-starter-actuator` and `micrometer-registry-prometheus` to `backend/pom.xml` (if not already present from T001)
- [x] T059 Run `quickstart.md` golden path validation — start backend (`./mvnw spring-boot:run`), start WordPress, execute all 12 steps from `quickstart.md` § 8 manually; confirm each step passes; fix any failures before marking complete

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately; all T001–T009 can run in parallel
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user story phases
- **Phase 3 (US1 Login)**: Depends on Phase 2 completion
- **Phase 4 (US2 List)**: Depends on Phase 2 completion — can run in parallel with Phase 3
- **Phase 5 (US3 Add)**: Depends on Phase 4 (`ContactService` and `ContactController` must exist)
- **Phase 6 (US4 Detail)**: Depends on Phase 5 (needs `ContactService` base)
- **Phase 7 (US5 Update)**: Depends on Phase 6 (needs `ContactSecurityService`)
- **Phase 8 (WordPress)**: Depends on Phase 3–7 backend being stable; T045–T058 can run in parallel
- **Phase 9 (Polish)**: After all feature phases

### Within Each Phase

- Tasks marked `[P]` have no file conflicts — run them in parallel
- Tasks without `[P]` must wait for the previous task in the same phase to complete

### Parallel Opportunities (Example — Phase 2)

```bash
# All these can run simultaneously:
Task T010: domain/Role.java
Task T011: domain/User.java
Task T012: domain/Contact.java
Task T013: domain/Address.java
Task T014: repository/UserRepository.java
Task T019: exception/ContactNotFoundException.java
Task T020: exception/DuplicateContactNameException.java
Task T022: service/ContactEventPublisher.java
Task T025: dto/LoginRequest.java
Task T026: dto/LoginResponse.java
Task T027: dto/PagedResponse.java
```

---

## Implementation Strategy

### MVP (User Story 1 only — Login works)

1. Complete Phase 1 (Setup)
2. Complete Phase 2 (Foundational)
3. Complete Phase 3 (US1 — Login)
4. **STOP and validate**: `POST /api/v1/auth/login` returns JWT; `POST` with wrong
   password returns `401`; protected route without token returns `401`
5. Demo/deploy if needed

### Incremental Delivery

```
Phase 1+2 → Foundation ready
+ Phase 3  → Login works (MVP demo)
+ Phase 4  → Contact list visible
+ Phase 5  → Contacts can be added
+ Phase 6  → Contact detail viewable
+ Phase 7  → Contacts can be edited
+ Phase 8  → WordPress UI connected end-to-end
+ Phase 9  → Production-ready observability
```

### Task Execution Guidance for Minimal-Context Models

Each task in this file is intentionally self-contained:
- The **file path** tells you where to create/edit
- The **description** tells you exactly what class/method/content to produce
- Cross-references point to the specific section of the source document
- No task requires reading more than one source document to complete
- When editing an existing file (e.g., T038, T039), the task names the exact method to add

---

## Notes

- `[P]` = different files, safe to parallelise
- `[USn]` label maps task to its user story for traceability
- Commit after each checkpoint (end of each phase) to keep history clean
- Secrets (`JWT_SECRET`, `DB_PASSWORD`) are never hardcoded — always from env vars
- Never expose JPA entities in HTTP responses — always use DTOs
- Admin uniqueness check is application-level only; DB constraint covers user-scope
