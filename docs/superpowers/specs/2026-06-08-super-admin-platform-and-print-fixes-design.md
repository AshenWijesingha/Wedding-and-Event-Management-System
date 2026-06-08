# Super-Admin Platform Features + Print/Quotation Fixes

Date: 2026-06-08
Branch: feat/profile-and-reporting (commit directly, per user)

## Goal

Two parallel goals on the EventPro multi-tenant platform (Laravel 11 + Vue 3 + Inertia):

1. **Fix the broken print/quotation scenarios** (ship first).
2. **Add the must-have super-admin platform-management features** that are still missing.

Each work-item gets its own GitHub issue; each commit references its issue.

## Part A — Print & Quotation Fixes (ship first)

### A1. Blank fields in quotation PDF (confirmed root cause)
`Admin\QuotationController::store` validates and stores line items under key `name`
(`items.*.name`). `Quotations/Create.vue` only binds `item.name` (no description input).
But `resources/views/pdf/quotation.blade.php:163` renders `$item['description']` → the
Description column is always blank.

Fix:
- Blade reads `$item['description'] ?? $item['name'] ?? ''` (resilient to both shapes).
- `Create.vue`: add an optional Description input; keep `name` required.
- Null-safe `number_format` for `guest_count` and item prices (avoid PHP 8 null deprecation).

### A2. Print view (in-browser print)
Inquiries and quotations only offer "Download PDF". Add browser-print:
- Routes: `GET admin/inquiries/{inquiry}/print`, `GET admin/quotations/{quotation}/print`.
- Each returns the existing blade rendered as plain HTML, with a small script that calls
  `window.print()` on load.
- A "Print" button beside "Download PDF" on both Show pages (opens print route in new tab).

### A3. PDF download hardening
- Keep try/catch but log full stack (`$e`) not just message, so future failures are diagnosable.
- Guarantee branding always has a `contact` key (BrandingService default) so the quotation
  footer never hits an undefined-key path.

### A4. Quotation status workflow
Quotations are created as `draft` with no way to advance them. Add:
- Controller actions + routes: `send` (draft→sent, stamp `sent_at`), `accept` (→accepted,
  `accepted_at`), `reject` (→rejected), `markExpired` (→expired).
- Status-transition guard (only legal transitions).
- Buttons on `Quotations/Show.vue` reflecting current status.

### A5. Inquiry → Quotation conversion
- "Create Quotation" on `Inquiries/Show.vue` links to `quotations/create` prefilled with the
  inquiry's client/venue/package/event details (query params consumed by Create.vue).
- Link new quotation back to the inquiry via existing `inquiry_id` column.

### A6. Role gate fix
`QuotationController::store` does `abort_unless($user->hasAnyRole(['admin','manager']), 403)`,
which blocks `super_admin` and `tenant_owner`. Broaden to include them.

## Part B — Super-Admin Platform Features

Current super_admin surface: tenant CRUD, plan CRUD, cross-tenant users, platform dashboard.
Missing (all four to be built):

### B1. Tenant impersonation ("Login as")
- `Admin\ImpersonationController@start($tenant)` / `@stop`, super_admin-only.
- Store original user id + flag in session; switch acting tenant context.
- Persistent banner in `AppLayout.vue`: "Impersonating {tenant} — Exit".
- Exit restores original super_admin session. Guard: cannot impersonate while impersonating.

### B2. Suspend / activate tenants
- Add `status` column to `tenants` (enum-ish string: `active` | `suspended`, default `active`)
  if not already present (verify migration).
- Middleware blocks users of a suspended tenant (except super_admin) with a clear message.
- Toggle action + button in `Tenants/Index.vue`.

### B3. Platform audit log
- `spatie/laravel-activitylog` is installed but unused. Add `LogsActivity` to Tenant, Plan,
  User, Quotation (log key attributes).
- `Admin\AuditLogController@index` (super_admin-only) + `Platform/AuditLog.vue`: paginated,
  cross-tenant, filter by subject type / causer.

### B4. Global platform settings
- `App\Settings\PlatformSettings` (spatie/laravel-settings) + migration: `platform_name`,
  `default_plan_id`, `signups_enabled` (bool), `support_email`.
- `Admin\PlatformSettingsController@edit/@update` (super_admin-only) + `Platform/Settings.vue`.

## Architecture / boundaries

- New controllers are thin; reuse existing services (BrandingService) and models.
- Print routes reuse PDF blades — single source of truth for document layout.
- Impersonation and suspension enforced in middleware, not scattered in controllers.
- Audit logging is declarative (trait on models), not manual calls.

## Testing

- Feature: quotation PDF contains item description; print routes return 200 HTML; quotation
  status transitions; inquiry→quotation prefill; store allowed for super_admin.
- Feature: impersonation start/stop swaps + restores session; suspended-tenant user blocked;
  audit log row written on tenant update; platform settings persist.
- Run full PHPUnit suite + `npm run build` green before push.

## Delivery

- Commit to `feat/profile-and-reporting`. One GitHub issue per item (A1–A6, B1–B4).
- Part A committed + pushed first, then Part B.
