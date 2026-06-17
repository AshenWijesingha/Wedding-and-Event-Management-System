# Public Demo Sandbox — Design Spec

**Date:** 2026-06-14
**Sub-project:** 4 of 5 in the redesign + demo roadmap
(1 design system ✓ → 2 onboarding wizard ✓ → 3 guided tours ✓ → **4 public demo sandbox** → 5 sales-demo script).
**Status:** Implemented on branch `feat/demo-sandbox` (7 tests; suite 237 green).

## Goal

Let an anonymous visitor click "Try the demo" on the landing page and land inside a fully working, **isolated, throwaway** EventPro workspace pre-filled with realistic data. Each visitor gets their own ephemeral demo tenant that expires after 60 minutes and is automatically reaped. Public provisioning is rate-limited and capped to protect the database.

## Context (verified 2026-06-14)

- App supports column-based multitenancy: `BelongsToTenant` adds a global `tenant` scope and stamps `tenant_id` on create from `Tenant::current()`; `SetCurrentTenant` resolves the current tenant from the auth user. This already isolates per-tenant data — the sandbox relies on it.
- `tenancy_mode` default `single`, but multiple tenants coexist fine under column scoping (super-admin already manages many tenants).
- Existing fixed demo tenant `grand-vista` + `DemoDataSeeder` (login `admin@demo.eventpro.test`) stays for local dev; the public sandbox creates NEW ephemeral tenants and does not touch it.
- Model factories exist for all entities (Venue/Package/Client/Booking/Quotation/Payment/Inquiry/Vendor/Staff/Task) — used to seed demo data quickly.
- Laravel scheduler is wired in `routes/console.php` (`Schedule::command(...)`).
- Landing "Try the demo" is currently a placeholder linking to `register`.
- `app.blade.php` already exposes a `csrf-token` meta tag.
- `User.preferences` cast `array`; `Tenant.settings` cast `array` with `getSetting/setSetting`.

## Decisions (locked)

1. **Ephemeral per-visitor** demo tenants (full isolation via existing scoping).
2. **60-minute lifetime**, reaped every **15 minutes**.
3. **Per-IP throttle + global live-tenant cap**.
4. Demo user is a **`tenant_owner`** (full admin experience). Guided tours auto-run; onboarding wizard skipped.

## Config / flag

`config/eventpro.php` gains a `demo` block:
```php
'demo' => [
    'enabled'          => env('DEMO_MODE', false),
    'lifetime_minutes' => env('DEMO_LIFETIME', 60),
    'max_live'         => env('DEMO_MAX_LIVE', 50),
    'throttle'         => env('DEMO_THROTTLE', 5), // per IP per hour
],
```
When `enabled` is false: `/demo/start` returns 404 and the landing button is hidden.

## Schema

Migration `add_demo_columns_to_tenants_table`:
- `is_demo` boolean, default false, indexed.
- `demo_expires_at` timestamp nullable.

Add both to `Tenant::$fillable`; cast `demo_expires_at` to `datetime`.

## Components

### `App\Services\DemoTenantProvisioner`
`provision(): array{tenant: Tenant, user: User}`
1. Create `Tenant` — `name` "Demo Workspace", unique slug `demo-{Str::random(8)}`, `is_demo=true`, `demo_expires_at = now()->addMinutes(lifetime)`, `status='active'`, a non-default `primary_color`.
2. `$tenant->makeCurrent()`.
3. Create `User` — `tenant_id`, `name` "Demo User", `email` `demo-{tenant id or random}@demo.local`, random password (`Str::password`), `role='tenant_owner'`, `is_active=true`, `email_verified_at=now()`; `assignRole('tenant_owner')`.
4. Seed isolated data with factories (counts kept modest, e.g. 3 venues, 4 packages, 6 clients, 8 bookings with related quotations/payments, a few inquiries/vendors/staff/tasks). **Pass `tenant_id` explicitly to every factory call** — the factories default `tenant_id` to a fresh `Tenant::factory()`, so omitting it would spawn extra tenants. Related records (booking → venue/client/package, payment → booking) must reference the just-created demo records, not factory-created ones.
5. `$tenant->setSetting('onboarding', ['seen' => true, 'dismissed' => true])`.

Pure orchestration; no HTTP concerns. Wrapped in a DB transaction.

### `App\Http\Controllers\DemoController@start`
Public `POST /demo/start`:
1. `abort_unless(config('eventpro.demo.enabled'), 404)`.
2. Global cap: count live demo tenants (`is_demo`, `demo_expires_at > now()`); if `>= max_live`, redirect back to landing with a friendly "The demo is at capacity — please try again shortly." (HTTP 303/redirect, not a hard error).
3. `['tenant'=>$t,'user'=>$u] = provisioner->provision()`.
4. `Auth::login($u)`; redirect `/admin`.

Route: `Route::post('/demo/start', [DemoController::class,'start'])->middleware('throttle:'.config('eventpro.demo.throttle').',60')->name('demo.start');` (public; throttle keyed by IP). Placed in public web routes, not the admin group.

### `demo:reap` command — `App\Console\Commands\ReapDemoTenants`
- Selects `Tenant::where('is_demo', true)->where('demo_expires_at','<',now())`.
- For each, inside a transaction: delete tenant-scoped rows via `withoutGlobalScopes()->where('tenant_id',$id)->delete()` across User, Booking, Payment, Quotation, Inquiry, Client, Venue, Package, Vendor, Staff, Task, CustomField (and any join tables, e.g. `booking_vendor`, cleaned by deleting bookings), then delete the tenant.
- Idempotent; logs count reaped.
- Scheduled: `Schedule::command('demo:reap')->everyFifteenMinutes();` in `routes/console.php`.

### Landing entry
Replace the placeholder "Try the demo" anchor in `resources/views/layouts/app.blade.php` with a CSRF `<form method="POST" action="{{ route('demo.start') }}">` button, wrapped in `@if(config('eventpro.demo.enabled'))`. Keep the editorial-pro styling.

### Demo banner + shared state
- `HandleInertiaRequests::share` adds `demo => ['is_demo'=>bool, 'expires_at'=>iso|null]` when the current tenant `is_demo` (null otherwise).
- `AppLayout.vue` renders a dismissible top banner when `$page.props.demo?.is_demo`: "You're exploring a live demo — it resets automatically. Changes won't be saved." Uses warning/info tokens.

## Abuse / safety

- **Throttle** `5/hour/IP` on `/demo/start` → 429 when exceeded.
- **Global cap** `max_live` live demo tenants → friendly redirect when reached.
- **TTL + reaper** guarantee cleanup even if abused between sweeps.
- Isolation via column tenancy + `BelongsToTenant`. Email is `log`, PayHere unconfigured/sandbox, so no real outbound from a demo. Heavy per-action guardrails are unnecessary given isolation + 60-min disposal.
- Demo tenants are excluded from the super-admin platform metrics? Out of scope unless trivial — note but do not block (real installs run with `DEMO_MODE` off, so platform stats are unaffected in production).

## Error handling

| Case | Behavior |
|---|---|
| `DEMO_MODE` off | `/demo/start` 404; landing button hidden |
| Live cap reached | Redirect to landing with capacity notice |
| Throttle exceeded | 429 (Laravel throttle response) |
| Provision fails mid-way | Transaction rolls back; no orphan tenant/user |
| Reaper runs with no expired demos | No-op |
| Non-demo tenant | Never reaped; no demo banner |

## Testing (sqlite-safe)

**Service**
- `provision` creates a tenant with `is_demo` + future `demo_expires_at`, a `tenant_owner` user, and seeded data scoped to that tenant; onboarding marked dismissed.
- Two provisions produce isolated data (a booking in demo A not visible under demo B's scope).

**Feature**
- `POST /demo/start` with `DEMO_MODE` on → authenticates a new demo user and redirects `/admin`; a new `is_demo` tenant exists.
- `DEMO_MODE` off → 404.
- At `max_live` cap → redirected with capacity message; no new tenant.
- Exceeding throttle → 429.

**Command**
- `demo:reap` deletes expired demo tenants and all their scoped rows; leaves un-expired demo tenants and non-demo tenants (and their data) intact.

**Shared state**
- `demo` banner prop present/true for a demo tenant session; absent/null otherwise.

## Out of scope (sub-project 5 / later)

Sales-demo script & talking points (sub-project 5); converting a demo into a paid account; demo analytics; excluding demo tenants from platform dashboards.
