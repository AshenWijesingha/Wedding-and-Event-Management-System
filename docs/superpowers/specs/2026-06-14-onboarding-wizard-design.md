# Onboarding First-Run Wizard — Design Spec

**Date:** 2026-06-14
**Sub-project:** 2 of 5 in the redesign + demo roadmap
(1 design system ✓ → **2 onboarding wizard** → 3 in-app demo tour → 4 public demo sandbox → 5 sales-demo script).
**Status:** Implemented on branch `feat/onboarding-wizard` (13 tests, suite 225 green). Redirect scoped to `GET /admin` only (not the whole admin group) to avoid intercepting other admin pages.

## Goal

A new tenant team (event company) currently lands on an empty admin dashboard with zero guidance. Give the tenant owner/admin a first-run **setup wizard** that walks them through the four things needed to make the product usable — branding, first venue, first package, first team member — plus a dashboard checklist that persists until setup is complete.

Configures the **existing** tenant (single-tenant white-label default, `tenancy_mode=single`). No public company self-signup (declined); no billing/plan selection.

## Context (verified 2026-06-14)

- `tenancy_mode` default `single`; super_admin provisions tenants via `Admin\TenantController::store`, which creates a `tenant_owner` user. Public `register` only creates `client` users → portal.
- No onboarding/setup flow exists anywhere.
- `Staff` is an HR directory record, **not** a login account — team login access means creating a `User` (role + password).
- `MAIL_MAILER=log` — email is a later sub-project, so invites cannot send mail this sub-project.
- `Tenant` has `getSetting($key,$default)` / `setSetting($key,$value)` over a JSON `settings` column.
- Design-system component lib (`@/Components/ui`) + tokens are available and must be used.
- Existing `venues.store` / `packages.store` / `settings.branding` endpoints redirect to their own index pages, so the wizard uses its own thin endpoints instead of reusing them directly.

## Design decisions

1. **First-run wizard for the tenant team**, gated to `super_admin|tenant_owner|admin`. Single cohesive Inertia wizard page, not one route per step.
2. **Redirect once.** Middleware redirects the first admin login to the wizard exactly once; thereafter the dashboard checklist drives completion.
3. **Progress derived from real data** (self-healing), with `seen`/`dismissed` flags in `tenant.settings['onboarding']`.
4. **Invite creates a real login User** with a temp password shown once (works offline).

## Progress model — `App\Services\OnboardingService`

Steps (each a boolean derived from data):

| Step | Done when |
|---|---|
| `branding` | tenant has a `logo`, OR a non-default `primary_color`, OR `settings.branding.company_name` is set |
| `venue` | `Venue` count ≥ 1 (current tenant) |
| `package` | `Package` count ≥ 1 (current tenant) |
| `team` | ≥ 1 login `User` with role in `admin|manager|staff` besides the founding owner |

- `completed` = all four true.
- `seen` / `dismissed` stored in `tenant.settings['onboarding']` (`['seen' => bool, 'dismissed' => bool]`).
- `show_checklist` = `!completed && !dismissed`.
- `should_redirect($user)` = user role ∈ `{super_admin, tenant_owner, admin}` && `!seen` && `!completed`.

Methods: `progress(Tenant): array` (per-step booleans + completed), `state(Tenant): array` (progress + seen/dismissed + show_checklist), `markSeen(Tenant)`, `dismiss(Tenant)`.

## Controller + routes — `App\Http\Controllers\Admin\OnboardingController`

In the admin group, additionally gated `role:super_admin|tenant_owner|admin`:

```
GET  /admin/onboarding            onboarding.show      // render wizard; calls markSeen()
POST /admin/onboarding/branding   onboarding.branding  // validate + tenant->update + setSetting('branding', ...)
POST /admin/onboarding/venue      onboarding.venue     // validate + Venue::create (tenant auto-scoped)
POST /admin/onboarding/package    onboarding.package   // validate + Package::create
POST /admin/onboarding/invite     onboarding.invite    // validate + create User + temp password
POST /admin/onboarding/finish     onboarding.finish    // dismiss() — used by "Skip for now" and final "Done"
```

- Each step endpoint validates with the same rules as the canonical store (venue: name/capacity/base_price required; package: name/price; branding: name/primary_color/logo/tagline), creates the record, then `redirect()->route('onboarding.show')` so the wizard advances with fresh progress. Validation errors return to the wizard with errors.
- **Invite**: validate `name`, `email` (unique users), `role` in `admin|manager|staff`. Create `User` with `tenant_id` = current tenant, generated temp password (`Str::password(12)`), `assignRole`. Flash the plaintext temp password once (`->with('invited_password', $pwd)` + invited email) so the wizard can display it. Never stored or logged in plaintext.
- Reuse models directly (`Venue::create`, `Package::create`, `User::create`) — `BelongsToTenant` + `SetCurrentTenant` set `tenant_id`. Branding mirrors `SettingsController::updateBranding`.

## Redirect middleware — `App\Http\Middleware\EnsureOnboarded`

Alias `onboarded`, applied inside the admin group. Behavior: if `OnboardingService::should_redirect($request->user())` is true **and** the current route is not an onboarding route and not `logout` → `redirect()->route('onboarding.show')`. Exempt by route-name prefix (`onboarding.*`) to avoid loops. Only redirects owner/admin roles; never staff/manager/client. Because `markSeen()` runs when the wizard renders, the redirect fires at most once per tenant.

## Shared Inertia state

`HandleInertiaRequests::share` adds `onboarding => OnboardingService::state(currentTenant)` **only when** the auth user is an admin-area role and a current tenant exists (null otherwise). This single source powers the dashboard checklist (and any future banner) without per-controller wiring.

## UI

- **`resources/js/Pages/Onboarding/Wizard.vue`** — editorial-pro stepper using `@/Components/ui` (Card, PageHeader, Button, TextField, SelectField, StatusBadge, Alert). Steps: Branding → Venue → Package → Team → Done. Left/top progress rail reflects derived `progress`; completed steps show a check and can be revisited. Each step is a small `useForm` posting to its endpoint. "Skip for now" posts `onboarding.finish`. The Team step, after a successful invite, shows the returned temp password once (from flash) with a copy affordance. Final "Done" posts `onboarding.finish` and redirects to `/admin`.
- **`resources/js/Components/OnboardingChecklist.vue`** — "Getting started" card listing the four steps with done/✓ state and deep links (Branding → wizard branding step / Settings, Venue → wizard, etc. — simplest: each links back into the wizard at that step). Rendered on `Pages/Dashboard.vue` when `$page.props.onboarding?.show_checklist`. Includes a "Dismiss" action → `onboarding.finish`.

## Error handling

| Case | Behavior |
|---|---|
| Validation error in a step | Return to wizard with field errors; no record created; progress unchanged |
| Invite email already a user | Validation error on `email` |
| Non-admin role hits onboarding routes | 403 via role middleware |
| Tenant already fully set up | No redirect; checklist hidden (`completed`) |
| Owner clicks Skip | `dismissed=true`; no future redirect; checklist hidden |
| No current tenant (edge) | Shared `onboarding` prop is null; middleware no-ops |

## Testing (sqlite-safe, no mail/GD deps)

**Service (unit)**
- `progress` flips each step true as the underlying record is created.
- `completed` only when all four; `show_checklist` respects `dismissed`.
- `should_redirect` true for fresh owner/admin, false once `seen`, false for staff/client.

**Feature**
- Fresh owner login → redirected to `/admin/onboarding` once; second request to `/admin` not redirected (seen set).
- `onboarding.venue` / `package` create a tenant-scoped record and advance progress.
- `onboarding.branding` persists branding settings.
- `onboarding.invite` creates a login `User` with the chosen role + a temp password returned once; the new user can authenticate; password not present on a later request.
- Skip (`onboarding.finish`) sets `dismissed`; no further redirect; `show_checklist` false.
- RBAC: `client` and `staff`/`manager` cannot access onboarding routes (403); only owner/admin.
- Tenant scoping: created venue/package/user belong to the acting tenant; not visible cross-tenant.
- Dashboard exposes `onboarding.show_checklist` true before setup, false after completion/dismiss.

## Out of scope (later sub-projects)

Real invite/confirmation emails (email sub-project), public company self-signup, billing/plan selection, the in-app product tour (sub-project 3).
