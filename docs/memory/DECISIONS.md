# Technical Decisions (`docs/memory/`)

This file stores durable technical and implementation decisions. For governance-level decisions or project standards, see `.specify/memory/DECISIONS.md`.

## Entry Lifecycle

Each decision follows this lifecycle:

```
Active → Needs Review → Superseded → (pruned)
```

- **Active**: The decision is current and must be honored by all features and AI agents.
- **Needs Review**: Implementation reality or new context suggests this decision may be outdated. It should still be honored until reviewed and explicitly changed.
- **Superseded**: A newer decision has replaced this one. Keep it for historical context until the next audit, then consider pruning.
- **Pruned**: During an audit, remove superseded entries that no longer provide historical value. This keeps the file focused.

### When to change status

| Current Status | Change To    | When                                                                                                       |
| -------------- | ------------ | ---------------------------------------------------------------------------------------------------------- |
| Active         | Needs Review | Verified implementation or tests contradict the decision, or recurring features follow a different pattern |
| Active         | Superseded   | A newer decision explicitly replaces this one                                                              |
| Needs Review   | Active       | Team confirms the decision still holds after review                                                        |
| Needs Review   | Superseded   | Team confirms a replacement decision                                                                       |
| Superseded     | _(remove)_   | Audit finds no remaining historical value                                                                  |

### Rules

- Never delete an Active decision without replacing or superseding it.
- Never silently ignore a decision. If it feels wrong, mark it Needs Review and resolve it.
- Keep at most 3–5 Superseded entries for context. Prune older ones during audits.

---

## 2026-05-24 — Laravel replaces WordPress as the Presentation layer

**Status**: Active

**Why this is durable**
Every frontend feature touches the Presentation layer technology choice. The decision to
use Laravel (not a CMS, not another micro-framework) shapes routing conventions, template
patterns, HTTP client usage, and testing approaches across all Phase 1 and Phase 2 UI work.

**Decision**
WordPress is removed. Laravel 11 (PHP 8.2+) is the sole Presentation layer framework.
Blade templates render all views. Laravel's Http facade (with explicit timeouts and retry)
is the only permitted way to call the Spring Boot REST API. No `wp_*` functions, hooks,
or shortcodes remain anywhere in the codebase. See ADR-008 for full rationale.

**Tradeoffs**
Gained: clean MVC, strict-type PHP, reduced attack surface, no CMS overhead, standard
`php artisan` developer tooling. Lost: existing WordPress templates and plugin code must
be rewritten; Docker Compose service topology changes (no MySQL, PHP-FPM + Nginx replaces
the WordPress container).

**Future mistake prevented**
Do not add WordPress-specific patterns (plugin hooks, `functions.php`, WP REST API
namespace, `admin-ajax.php`) to the codebase. This decision explicitly rules them out.

**Evidence**
ADR-008 (2026-05-24). Constitution updated to v2.0.0 (2026-05-24).

**Where to look next**
`docs/adr/ADR-008-php-frontend-migration.md`, `.specify/memory/constitution.md` (v2.0.0),
`frontend/` directory (Laravel app root), `specs/002-php-frontend-migration/spec.md`.

---

## 2026-05-22 — WordPress chosen as Presentation layer (Superseded)

**Status**: Superseded by "Laravel replaces WordPress as the Presentation layer" (2026-05-24)

**Decision**
WordPress 6.4 (PHP 8.1+) was the initial Presentation layer. All UI was delivered as
a child theme + a custom plugin consuming the Spring Boot REST API.

**Evidence**
ADR-001 (2026-05-22), partially superseded annotation added 2026-05-24.
