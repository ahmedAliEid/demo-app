# Tasks — Contact & Login UI Enhancement: Red, White, and Black Theme

**Feature**: 003-contact-login-ui
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Research**: [research.md](./research.md)

**Total Tasks**: 22
**Organization**: Grouped by user story for independent implementation and testing

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

---

## Phase 1: Setup (Design Tokens & Global Reset)

**Purpose**: Establish the colour token system and global base styles that all user story phases depend on

- [x] T001 Add `:root` CSS custom properties block to top of `frontend/resources/css/app.css` with all 10 colour tokens: `--color-primary: #CC0000`, `--color-primary-hover: #A80000`, `--color-primary-light: #FFF0F0`, `--color-dark: #1A1A1A`, `--color-white: #FFFFFF`, `--color-border: #E0E0E0`, `--color-surface: #F5F5F5`, `--color-text-muted: #666666`, `--color-success: #2E7D32`, `--color-success-light: #F0FFF0`
- [x] T002 Add global reset to `frontend/resources/css/app.css` after the `:root` block: `*, *::before, *::after { box-sizing: border-box; }` and `body { margin: 0; font-family: system-ui, sans-serif; background: var(--color-white); color: var(--color-dark); line-height: 1.5; }`

**Checkpoint**: CSS tokens and reset in place — all subsequent tasks reference `var(--color-*)` only, no hex literals

---

## Phase 2: Foundational (Header & Navigation)

**Purpose**: Dark header with white logo and red logout button — shared across all pages, must be complete before any page can be visually verified

**⚠️ CRITICAL**: All user story visual verification depends on this phase

- [x] T003 In `frontend/resources/views/layouts/app.blade.php`: wrap the `<header>` element in a new `<div class="demo-header">...</div>` and ensure `<nav>` has class `demo-nav`; confirm `.demo-logo` and `.demo-logout-btn` classes are already present on their elements
- [x] T004 In `frontend/resources/css/app.css`: add `.demo-header { background: var(--color-dark); width: 100%; }`, `.demo-nav { display: flex; align-items: center; justify-content: space-between; max-width: 960px; margin: 0 auto; padding: .75rem 1rem; }`, `.demo-logo { color: var(--color-white); font-weight: 700; font-size: 1.2rem; text-decoration: none; }`, `.demo-role-badge { background: var(--color-primary); color: var(--color-white); padding: .2rem .6rem; border-radius: 4px; font-size: .8rem; }`, `.demo-logout-btn { background: var(--color-primary); color: var(--color-white); border: none; padding: .4rem .9rem; border-radius: 4px; cursor: pointer; }` and `.demo-logout-btn:hover { background: var(--color-primary-hover); }`

**Checkpoint**: Dark header with white logo and red logout button visible on all pages

---

## Phase 3: User Story 1 — Visually Refreshed Login Page (Priority: P1) 🎯 MVP

**Goal**: Login page rendered with the red, white, and black design system — accessible, focused, and visually polished

**Independent Test**: Open `http://localhost:8888/login` without authentication; confirm dark header, white form card with red submit button, red focus rings on inputs, and red validation errors on failed submission

- [x] T005 [US1] Update `.demo-login-form` in `frontend/resources/css/app.css`: replace `border: 1px solid #ddd` with `border: 1px solid var(--color-border)` and add `box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: 8px;`
- [x] T006 [US1] Update `.demo-login-form label` in `frontend/resources/css/app.css`: add `color: var(--color-dark);`
- [x] T007 [US1] Update `.demo-login-form input[type="text"], .demo-login-form input[type="password"]` in `frontend/resources/css/app.css`: replace `border: 1px solid #ccc` with `border: 1px solid var(--color-border); background: var(--color-white); color: var(--color-dark);` and add focus state `:focus { border-color: var(--color-primary); outline: 2px solid var(--color-primary-light); outline-offset: 0; }`
- [x] T008 [US1] Update `.demo-login-form button[type="submit"]` in `frontend/resources/css/app.css`: replace `background: #0073aa` with `background: var(--color-primary); color: var(--color-white);` and add `:hover { background: var(--color-primary-hover); }` and `:focus { outline: 2px solid var(--color-primary); outline-offset: 2px; }`

**Checkpoint**: Login page fully styled — dark header, white card, red button, accessible focus states. User Story 1 independently testable.

---

## Phase 4: User Story 2 — Visually Refreshed Contacts List Page (Priority: P2)

**Goal**: Contacts list with dark table headers, red hover rows, red search button, and consistent pagination using the design system

**Independent Test**: Log in and navigate to `http://localhost:8888/contacts`; confirm dark table headers, red row hover highlight, red search/filter button, red active pagination item, and a styled empty state if no contacts exist

- [x] T009 [P] [US2] Update `.demo-notice-error` in `frontend/resources/css/app.css`: replace `background: #fde; border-left: 4px solid #c00` with `background: var(--color-primary-light); border-left: 4px solid var(--color-primary); color: var(--color-dark);`
- [x] T010 [P] [US2] Update `.demo-notice-success` in `frontend/resources/css/app.css`: replace `background: #dfd; border-left: 4px solid #090` with `background: var(--color-success-light); border-left: 4px solid var(--color-success); color: var(--color-dark);`
- [x] T011 [US2] Update `.demo-contacts-table th` in `frontend/resources/css/app.css`: replace `background: #f5f5f5` with `background: var(--color-dark); color: var(--color-white);`
- [x] T012 [US2] Update `.demo-contacts-table th, .demo-contacts-table td` border in `frontend/resources/css/app.css`: replace `border-bottom: 1px solid #eee` with `border-bottom: 1px solid var(--color-border);`
- [x] T013 [US2] Update `.demo-contacts-table tr:hover td` in `frontend/resources/css/app.css`: replace `background: #fafafa` with `background: var(--color-primary-light);`
- [x] T014 [US2] Update `.demo-search-form input, .demo-search-form select` in `frontend/resources/css/app.css`: replace `border: 1px solid #ccc` with `border: 1px solid var(--color-border);`
- [x] T015 [US2] Update `.demo-search-form button` in `frontend/resources/css/app.css`: replace `background: #0073aa` with `background: var(--color-primary); color: var(--color-white);` and add `:hover { background: var(--color-primary-hover); }`
- [x] T016 [US2] Update `.demo-pagination` styles in `frontend/resources/css/app.css`: replace `border: 1px solid #ddd` with `border: 1px solid var(--color-border); color: var(--color-dark);`; update `.demo-pagination a.active` to `background: var(--color-primary); color: var(--color-white); border-color: var(--color-primary);`; add `.demo-pagination a:hover { border-color: var(--color-primary); color: var(--color-primary); }`
- [x] T017 [US2] Update `.demo-empty-state` in `frontend/resources/css/app.css`: replace `color: #666` with `color: var(--color-text-muted);`

**Checkpoint**: Contacts list fully styled — dark headers, red hovers, red action buttons. User Story 2 independently testable.

---

## Phase 5: User Story 3 — Visually Refreshed Contact Detail & Form Pages (Priority: P3)

**Goal**: Contact detail and create/edit form pages use the full design system with red action buttons, accessible inputs, red validation errors, and consistent button hierarchy

**Independent Test**: Navigate to a contact detail page and the create/edit form; confirm red Save button, red field validation errors, red back-link, and styled error pages (404, 500)

- [x] T018 [US3] Update `.demo-contact-form input[type="text"]` in `frontend/resources/css/app.css`: replace `border: 1px solid #ccc` with `border: 1px solid var(--color-border); color: var(--color-dark);` and add `:focus { border-color: var(--color-primary); outline: 2px solid var(--color-primary-light); outline-offset: 0; }`
- [x] T019 [US3] Update `.demo-contact-form button[type="submit"]` in `frontend/resources/css/app.css`: replace `background: #0073aa` with `background: var(--color-primary); color: var(--color-white);` and add `:hover { background: var(--color-primary-hover); }`
- [x] T020 [US3] Update `.demo-contact-form .field-error` and `.required` in `frontend/resources/css/app.css`: replace `color: #c00` with `color: var(--color-primary);`
- [x] T021 [US3] Update `.demo-back-link` in `frontend/resources/css/app.css`: replace `color: #0073aa` with `color: var(--color-primary);` and add `:hover { color: var(--color-primary-hover); }`
- [x] T022 [P] [US3] Add `.demo-error-page` styles to `frontend/resources/css/app.css`: `{ text-align: center; padding: 4rem 1rem; }` with `.demo-error-page h1 { color: var(--color-primary); font-size: 4rem; margin-bottom: .5rem; }` and `.demo-error-page p { color: var(--color-dark); }` and `.demo-error-page a { color: var(--color-primary); }` and `.demo-error-page a:hover { color: var(--color-primary-hover); }`

**Checkpoint**: All pages fully styled. User Story 3 independently testable.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Sync compiled output, responsive verification, and accessibility validation

- [x] T023 Copy `frontend/resources/css/app.css` to `frontend/public/css/app.css` so the running Docker container serves the updated styles
- [ ] T024 Start the Docker Compose stack (`docker compose up -d`) and manually verify the full flow: login page → submit login → contacts list → contact detail → create contact form → edit contact form → logout
- [ ] T025 Verify WCAG 2.1 AA contrast for the 6 key colour pairs from `research.md` using browser DevTools accessibility panel: white on `#CC0000` (≥4.5:1), `#1A1A1A` on white (≥4.5:1), white on `#1A1A1A` (≥4.5:1), `#CC0000` on `#FFF0F0` (≥4.5:1 at bold), `#666666` on white (≥4.5:1)
- [ ] T026 Test responsive layout at 375px and 1440px viewport widths; confirm no overlapping, truncated, or unreadable elements on any of the four page types

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 (T001, T002) — **BLOCKS all visual verification**
- **US1 (Phase 3)**: Depends on Phase 2 (T003, T004) — P1 priority, MVP scope
- **US2 (Phase 4)**: Depends on Phase 2 completion — can start in parallel with US1 (different CSS rules, no file conflicts)
- **US3 (Phase 5)**: Depends on Phase 2 completion — can start in parallel with US1 and US2
- **Polish (Phase 6)**: T023 must run after ALL CSS changes are complete; T024–T026 follow T023

### User Story Dependencies

- **US1 (P1)**: Depends on Foundational only; no dependency on US2 or US3
- **US2 (P2)**: Depends on Foundational only; no dependency on US1 or US3
- **US3 (P3)**: Depends on Foundational only; no dependency on US1 or US2

All three user stories modify **different CSS rule blocks** in `app.css` — they CAN be worked on in parallel by different agents or sequentially without conflict.

### Within Each User Story

- All CSS rule updates within a story target different selectors — fully parallelizable
- T009 and T010 (notice styles) are shared infrastructure but assigned to US2 as the first page that shows notices post-login

---

## Parallel Example: All User Stories Together

```bash
# After Phase 1 + Phase 2 complete, all three stories can proceed simultaneously:
Task T005–T008  → US1: Login form styles
Task T009–T017  → US2: Contacts list styles + notices
Task T018–T022  → US3: Contact form/detail styles + error pages
```

## Parallel Example: Within US2

```bash
# All within US2 target different selectors — run together:
Task T009: Update .demo-notice-error
Task T010: Update .demo-notice-success
# Then sequentially:
Task T011: Update table th (dark headers)
Task T012: Update table borders
Task T013: Update table row hover
Task T014: Update search inputs
Task T015: Update search button
Task T016: Update pagination
Task T017: Update empty state
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T002)
2. Complete Phase 2: Foundational (T003–T004)
3. Complete Phase 3: User Story 1 — Login page (T005–T008)
4. Copy CSS to public (T023) and verify login page visually
5. **STOP and VALIDATE**: Login page looks correct with red/white/black theme

### Incremental Delivery

1. Setup + Foundational → design tokens and dark header ready
2. User Story 1 → Login page restyled (MVP) → demo
3. User Story 2 → Contacts list restyled → demo
4. User Story 3 → Detail/form/errors restyled → demo
5. Polish → sync, responsive check, contrast verification

---

## Notes

- ALL hex colour literals (`#0073aa`, `#ccc`, `#ddd`, `#c00`, `#fde`, `#dfd`, `#f5f5f5`, `#666`, `#fafafa`, `#eee`) in `app.css` MUST be replaced with `var(--color-*)` references — no hex literals should remain after T001–T022
- `[P]` tasks = different CSS selectors, no blocking dependencies — safe to run in parallel
- T023 (sync to public/) MUST be the last CSS task before visual verification
- The Docker container volume-mounts `./frontend:/var/www/html`, so editing `resources/css/app.css` locally is live — but `public/css/app.css` must still be manually synced (T023)
