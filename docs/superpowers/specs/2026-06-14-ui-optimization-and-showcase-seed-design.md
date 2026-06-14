# UI Optimization + Showcase Seed — Design Spec

Date: 2026-06-14
Branch: `feat/ui-optimization`

## Goal

Make the whole EventPro UI as polished, consistent, responsive, and accessible as
possible, and ship a large showcase dataset (10+ of every entity) so every page,
report, and the calendar look full and realistic for demos.

The editorial-pro design system, ui component library, shells, onboarding, tours,
and the curated per-visitor demo provisioner already exist (see memory
`project-design-system`). This work is a **polish + audit + data** effort that
reuses those primitives — it does NOT reintroduce hardcoded colors or new design
tokens.

## Sub-projects

### SP-A — Showcase seed set

A new `ShowcaseSeeder` that provisions a dedicated tenant (`slug = showcase`,
name "Showcase Events Co.") so the existing fixed `grand-vista` demo and the
per-visitor provisioner stay untouched.

- 10+ of every entity, created via the existing factories with an **explicit
  `tenant_id`** on every call (factory defaults spawn new tenants).
- Entities: users (owner/admin/manager/staff logins), venues, packages, clients,
  inquiries, quotations, bookings, payments, vendors, staff, tasks.
- Relations wired so detail pages render fully: bookings reference real
  venues/packages/clients; quotations reference inquiries/clients; payments
  reference bookings; tasks reference bookings/staff.
- Bookings spread across statuses (pending, confirmed, completed, cancelled) and
  across past + future dates (so Reports buckets and the availability calendar
  show data).
- Inquiry statuses respect the enum: `pending/contacted/qualified/proposal_sent/
  negotiating/converted/closed` (no `quoted`/`new` — CHECK constraint).
- Idempotent: `updateOrCreate` the tenant + login users; safe to re-run.
- Registered in `DatabaseSeeder` after `DemoDataSeeder`.
- Login: `owner@showcase.eventpro.test` / `password`.
- Test `ShowcaseSeederTest` asserts ≥10 of each entity for the showcase tenant.

### SP-B — Shared-shell UX pass (highest leverage — affects all pages)

- **AppLayout**: persist sidebar open/closed (localStorage), `aria-current="page"`
  on active nav link, skip-to-content link, visible focus rings, `aria-label`s on
  icon-only buttons (open/close sidebar, logout).
- **DataTable**: responsive — stays a table on `sm+`, becomes a stacked card list
  on mobile via per-column `label` headers; sticky header; `scope="col"` on th;
  optional `loading` state with skeleton rows; keep token classes.
- **ui/* a11y**: Modal focus trap + `role="dialog"`/`aria-modal` + Esc close +
  return focus; Button `aria-busy` when loading + focus-visible ring; FormField
  links label→input via `for`/`id` and errors via `aria-describedby`; Toggle
  `role="switch"`/`aria-checked`.

### SP-C — Page audit + visual polish

- Grep all `resources/js/Pages/**/*.vue` for legacy/hardcoded styles
  (`indigo-`, `gray-`, raw `<table`, raw `<button`, `bg-white`, `text-gray`) and
  migrate stragglers to ui-lib components + tokens.
- Dashboard + Portal/Dashboard visual lift: richer StatCards (trend/iconography),
  consistent EmptyStates, subtle enter transitions, consistent PageHeader usage.
- No behavioral/route/controller changes — presentational only.

## Non-goals

- No new design tokens, no new color palette, no BrandingService changes.
- No backend/route/controller logic changes (seeder + presentational only).
- No change to `grand-vista` DemoDataSeeder or the demo provisioner.

## Testing / verification

- `php artisan test` — full suite stays green; add `ShowcaseSeederTest`.
- `php artisan migrate:fresh --seed` runs clean; showcase tenant populated.
- `npm run build` clean.
- Grep-guard: no `indigo-`/`bg-gray-`/`text-gray-` left in `resources/js/Pages`.
- Headless screenshots of dashboard + a list page at desktop + mobile widths.

## Order

SP-A (concrete, testable) → SP-B (shared, high-leverage) → SP-C (long-tail polish).
