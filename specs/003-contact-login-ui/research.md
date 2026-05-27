# Research: Contact & Login UI Enhancement — Red, White, and Black Theme

**Feature**: 003-contact-login-ui | **Phase**: 0 — Research
**Date**: 2026-05-27

---

## Decision 1: Colour Palette Definition

**Decision**: Use the following tokens across all pages:

| Token | Hex | Role |
|---|---|---|
| `--color-primary` | `#CC0000` | Primary action (buttons, links, active states) |
| `--color-primary-hover` | `#A80000` | Hover state for primary elements |
| `--color-primary-light` | `#FFF0F0` | Subtle tinted backgrounds (error notices, highlights) |
| `--color-dark` | `#1A1A1A` | Header/nav background, body text |
| `--color-white` | `#FFFFFF` | Page background, card surfaces |
| `--color-border` | `#E0E0E0` | Dividers, input borders, table row borders |
| `--color-surface` | `#F5F5F5` | Table headers, alternate row tints, form section backgrounds |
| `--color-text-muted` | `#666666` | Secondary text, placeholders, empty-state messages |
| `--color-success` | `#2E7D32` | Success notices (kept green — universally understood) |
| `--color-success-light` | `#F0FFF0` | Success notice background |

**Rationale**: `#CC0000` passes WCAG 2.1 AA contrast on white (4.6:1 for normal text sized ≥18px bold; used only as button background with white text achieving 5.9:1). Near-black `#1A1A1A` on white achieves 16.1:1, well above AA. Success notices retain green as per universal convention — changing to red would confuse users.

**Alternatives considered**: `#E53935` (Material Red 600 — rejected, lighter contrast on white), `#B71C1C` (too dark, loses vibrancy at small sizes).

---

## Decision 2: Header / Navigation Treatment

**Decision**: Give the `<header>` a full-width dark (`#1A1A1A`) background spanning the viewport, with white logo text and nav links, and a red logout button. This separates chrome from content and gives pages a strong brand anchor.

**Rationale**: Dark header + white content area is the canonical red/white/black layout pattern (e.g., YouTube, Coca-Cola's web properties). It creates clear visual hierarchy without requiring heavy redesign of page content areas.

**Alternatives considered**: Red header background — rejected because red-on-red page content would reduce contrast; white header — rejected as it blends into the page body and looks unfinished.

---

## Decision 3: Scope of Blade Template Changes

**Decision**: Minimise Blade template changes. Only `layouts/app.blade.php` needs structural changes (add header wrapper `<div class="demo-header">` and update nav classes). All other templates (`login.blade.php`, `index.blade.php`, `show.blade.php`, `form.blade.php`, `errors/`) need no structural changes — only the CSS rewrite applies to their existing class names.

**Rationale**: The spec's FR-011 forbids breaking existing functionality. Keeping template edits to the layout only reduces regression risk. All existing class names (`demo-login-form`, `demo-contacts-table`, `demo-search-form`, etc.) are preserved.

**Alternatives considered**: Introducing Blade components for buttons and inputs — deferred; adds scope without being required by the spec.

---

## Decision 4: CSS Architecture

**Decision**: Rewrite `frontend/resources/css/app.css` in place. Define CSS custom properties (variables) at `:root` for all colour tokens. Use the existing class-name system. No CSS framework, no pre-processor, no build step — plain CSS only, consistent with the current implementation.

**Rationale**: The project has no existing CSS build pipeline. Introducing Sass/PostCSS would add infrastructure scope not covered by the spec. CSS variables provide the same design-token benefit with no tooling change.

**Alternatives considered**: Tailwind CSS — rejected (requires build step and significant template changes); Bootstrap — rejected (adds large dependency, conflicts with existing class naming).

---

## Decision 5: Accessibility Baseline

**Decision**: Target WCAG 2.1 Level AA for all colour pairs used in the redesign. Verify the following critical pairs:

| Foreground | Background | Ratio | Passes AA? |
|---|---|---|---|
| `#FFFFFF` (white) | `#CC0000` (primary btn) | 5.9:1 | ✅ AA |
| `#1A1A1A` (text) | `#FFFFFF` (white bg) | 16.1:1 | ✅ AA |
| `#1A1A1A` (text) | `#F5F5F5` (surface) | 14.7:1 | ✅ AA |
| `#FFFFFF` (nav text) | `#1A1A1A` (header bg) | 16.1:1 | ✅ AA |
| `#CC0000` (error text) | `#FFF0F0` (error bg) | 4.5:1 | ✅ AA (borderline — use bold/large) |
| `#666666` (muted) | `#FFFFFF` | 5.7:1 | ✅ AA |

**Rationale**: All pairs used pass AA. The error text/bg combination (`#CC0000` on `#FFF0F0`) passes at exactly 4.5:1 — error text is always rendered ≥14px bold (via `.demo-notice-error`), meeting the large-text threshold of 3:1 with margin.
