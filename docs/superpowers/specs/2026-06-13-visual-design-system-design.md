# Visual Design System + Redesign Foundation — Design Spec

**Date:** 2026-06-13
**Sub-project:** 1 of 5 in the product-wide redesign + demo roadmap
(1 design system → 2 onboarding wizard → 3 in-app demo tour → 4 public demo sandbox → 5 sales-demo script).
**Status:** Implemented on branch `feat/visual-design-system`.

## Goal

Replace the feature-first, inconsistent UI with a cohesive **editorial-pro** visual language driven by design tokens, a reusable component library, correct per-context shells, and migrate all pages to it. White-label preserved: tenants still override the accent only.

## Problem (verified 2026-06-13)

- 44 page/layout files hardcoded `indigo-600`/`gray-900` instead of the existing CSS-var tokens.
- Only 5 shared components (Breeze-era); `PrimaryButton` itself hardcoded indigo.
- Status-color maps copy-pasted per page.
- `AppLayout.vue` (admin sidebar) reused by 5 Portal pages, but its nav is permission-gated to admin abilities → client portal users saw a near-empty/incorrect sidebar.

## Decisions (locked)

- **Editorial-pro**: Inter UI/body, Fraunces (`font-display`) headings/wordmark, warm-neutral surfaces, soft elevation, roomy spacing.
- **Indigo** stays the default accent; tenant override via `BrandingService` (`--color-primary*` only).
- Migrate **all ~70 pages** in this sub-project.

## What shipped

1. **Tokens** — static warm-neutral + status CSS vars in `resources/css/app.css` (`--surface*`, `--border*`, `--ink*`, `--success/warning/danger/info` + `-soft`, radius). Mapped in `tailwind.config.js` as `surface/border/ink/success/warning/danger/info` + `fontFamily.display`. Accent vars left to `BrandingService`.
2. **Component library** `resources/js/Components/ui/` (barrel `index.js`): Button, Card, PageHeader, StatCard, DataTable, StatusBadge, EmptyState, Alert, FormField + TextField/SelectField/TextareaField, Toggle, Avatar, Modal, Tabs. Status colors centralized in `resources/js/utils/status.js`. Legacy `PrimaryButton`/`SecondaryButton` now shim over `Button`.
3. **Shells** — `AppLayout.vue` (admin) restyled to tokens + `info` flash; new `PortalLayout.vue` with correct client nav (fixes empty-sidebar bug); 5 Portal pages repointed + migrated. `GuestLayout.vue` already on-brand (dark luxe editorial).
4. **Migration** — `scripts/token-codemod.mjs` swapped hardcoded indigo/gray → tokens across 59 Vue files; Portal pages hand-migrated to components.
5. **Landing** — `welcome.blade.php` already editorial-pro; added header "Create account" CTA + footer "Try the demo" placeholder (repointed at the public sandbox in sub-project 4).

## Token separation

`BrandingService::getCssVariables()` emits only `--color-primary/secondary/accent`. Neutrals + status tokens are static in `app.css`, so changing a tenant's `primary_color` swaps the accent app-wide while the editorial-pro surface/ink/border language stays fixed.

## Verification (all passed)

- `npm run build` clean.
- `php artisan test` — 212 passed (no backend touched).
- Grep guard: no `indigo-600`/`bg-gray-900`/`indigo-700` left in `resources/js` (outside intentional shims).
- Headless screenshots: landing + login render editorial-pro (Fraunces, tokens, indigo accent).

## Out of scope (later sub-projects)

Onboarding wizard, demo tenant + in-app tour, public demo sandbox + scheduled reset, sales-demo script. No backend/route/model/permission changes in this sub-project.
