# Super Admin Platform — Design Spec

_Date: 2026-06-09_

## Context

EventPro is a multi-tenant Laravel + Inertia/Vue wedding & event management app. A platform
`super_admin` (null `tenant_id`) already exists with cross-tenant controls bolted onto the
shared tenant-admin area (`/admin`): platform dashboard, tenant CRUD + suspend/activate,
impersonation, audit log, platform settings, plans CRUD, system-wide user management. Auth is
tenant-agnostic via `App\Auth\TenantlessUserProvider` (driver `eloquent-tenantless`).

The super admin currently shares the tenant login (`/login`) and `AppLayout`. The goal is a
**dedicated, isolated super-admin experience**: its own login and dashboard, full control over
every tenant, plus platform operations — database backup/restore, system diagnostics, and
operational tooling — at an industry-grade quality bar.

### Decisions (from brainstorming)
- **DB infra ops = safe subset.** Backup (download dump), restore (upload/select + typed confirm
  + automatic pre-restore safety backup), system diagnostics, and a documented migration guide.
  **No runtime live-DB connection swapping** (rejected as a data-loss / security footgun; that is
  an ops/infra concern, not app-UI functionality).
- **DB engine = auto-detect.** Support SQLite and MySQL/MariaDB (Postgres best-effort), keyed off
  the active connection driver.
- **Delivery = phased**, must-have first, review between phases.
- **Relocate** existing `Platform/*` pages and platform `/admin` routes into the new
  `/super-admin` area; redirect the old routes.
- **All four** Phase-3 nice-to-haves included: scheduled auto-backups, queue/failed-jobs monitor,
  global cross-tenant search, feature flags + broadcast.

## Goals / Non-Goals

**Goals**
- Separate super-admin login + dashboard + layout, isolated from the tenant admin area.
- Super admin can manage everything across all tenants from one place.
- Safe, audited database backup & restore with engine auto-detection.
- System diagnostics / health visibility.
- Operational nice-to-haves (scheduled backups, queue monitor, global search, feature flags,
  broadcast).

**Non-Goals**
- Runtime swapping of the live database connection from the UI.
- A "migrate to another platform" wizard that rewrites the live connection. Replaced by an
  export + documented import procedure.
- Changing the underlying tenant-app feature set.

## Architecture

### Routing & access control
- New guest route: `GET /super-admin/login` (+ `POST`), throttled. Renders `SuperAdmin/Login`.
- New group: `Route::prefix('super-admin')->middleware(['auth','role:super_admin'])->name('super-admin.')`.
- **Reuse** the existing spatie `role:super_admin` middleware (already used by the current platform
  routes) — no new gate middleware needed. The only new auth behavior is the post-login redirect.
- Redirect logic in `AuthenticatedSessionController@store`: if `user->isSuperAdmin()` →
  `route('super-admin.dashboard')`, else existing `route('admin.dashboard')`. The dedicated
  `/super-admin/login` posts through the same tenant-agnostic provider but always lands in the
  super-admin area; a non-super-admin authenticating there is redirected to `/admin`.
- Old platform routes under `/admin` (`tenants.*`, `plans.*`, `audit-log.*`, `platform-settings.*`,
  `impersonate.start`) move under `/super-admin` and keep their controllers. Add redirects from the
  old `admin.tenants.index` etc. names to the new ones to avoid broken links.

### Frontend
- `resources/js/Layouts/SuperAdminLayout.vue` — distinct sidebar/topbar (visually differentiated
  from tenant `AppLayout` so an operator always knows they are in the platform console). Nav:
  Dashboard, Tenants, Users, Plans, Audit Log, Settings, and a **System** group (Diagnostics,
  Backups, Queue, Search, Feature Flags, Broadcast).
- Move `Pages/Platform/*` → `Pages/SuperAdmin/*`; add new pages per phase. All use
  `SuperAdminLayout`.
- New `Pages/SuperAdmin/Login.vue` (own guest layout, distinct from tenant `GuestLayout`).

### Backend services (new)
- `App\Services\Platform\BackupService` — create/list/download/delete backups; engine auto-detect.
- `App\Services\Platform\RestoreService` — validate + safety-backup + import.
- `App\Services\Platform\DiagnosticsService` — collect health checks.
- Existing `BrandingService`, audit logging, `PlatformSettings` reused.

### Auditing & safety (cross-cutting)
- Every destructive action (restore, backup delete, tenant delete, feature-flag change, broadcast)
  writes to the existing audit log with actor, target, before/after where relevant.
- Destructive UI actions require typed confirmation (e.g. type the tenant name / `RESTORE`).
- Backup/restore/diagnostics routes are super-admin-only and rate-limited.

## Phases

### Phase 1 — Foundation + tenant control + diagnostics (MUST-HAVE)
1. `EnsureSuperAdmin` wiring + `/super-admin` route group + redirects from old `/admin` platform routes.
2. `SuperAdmin/Login.vue` + dedicated login route + post-login redirect logic.
3. `SuperAdminLayout.vue`; relocate `Platform/*` pages → `SuperAdmin/*`.
4. `/super-admin` dashboard — reuse `DashboardController@platform` stats; add quick links to System tools.
5. **Tenant control hub**: tenant list (existing) + **tenant detail drill-down** page showing that
   tenant's users, bookings count/revenue, plan, status, recent activity (queried with
   `withoutTenantScope` / `Tenant::makeCurrent()` as appropriate). Actions: edit, suspend/activate,
   impersonate, delete (typed confirm).
6. **System Diagnostics** page + `DiagnosticsService`: DB connect + driver/version, pending
   migrations count, cache get/put, queue connection, `storage/` + `bootstrap/cache` writable,
   disk free space, PHP + Laravel versions, `APP_ENV`/`APP_DEBUG`, failed-jobs count, scheduler
   last-run (if available). Read-only.

### Phase 2 — Backup & Restore (safe subset, auto-detect)
1. `BackupService::create()` — detect driver:
   - sqlite → copy the DB file;
   - mysql → `mysqldump` via configured creds to a `.sql` file;
   - pgsql → `pg_dump` (best-effort).
   Store under `storage/app/backups/{timestamp}-{driver}.{ext}` with a small JSON manifest
   (driver, size, app version, created_by, created_at).
2. Backups index page: list, download, delete (audited), show size/date/driver.
3. `RestoreService::restore($backup)` — typed confirm → **auto pre-restore safety backup** →
   import (sqlite: replace file; mysql: pipe into `mysql`; pgsql: `psql`). Maintenance mode during
   restore; clear caches after. Fully audited.
4. `docs/super-admin/migration-guide.md` — export here, import on target platform; no runtime swap.

### Phase 3 — Nice-to-haves (ALL included)
1. **Scheduled auto-backups**: config (`config/backup.php` or platform settings) for
   frequency + retention; `php artisan platform:backup` command scheduled in `routes/console.php`;
   prune beyond retention.
2. **Queue / failed-jobs monitor**: list failed jobs, retry one/all, flush; buttons for
   `cache:clear` / `config:clear` / `optimize` (audited).
3. **Global cross-tenant search**: search tenants + users (name/email) platform-wide; deep-links to
   tenant detail.
4. **Feature flags + broadcast**: per-tenant and per-plan boolean flags stored on tenant settings /
   plan; a service `featureEnabled($tenant,$flag)`. Broadcast: compose a notice/email to all (or
   selected) tenant admins.

## Data / schema changes
- Phase 2: no schema change (filesystem backups + JSON manifest). Optionally a `backups` table later
  if DB-tracked history is wanted — deferred (YAGNI).
- Phase 3 feature flags: store under existing tenant `settings` JSON and plan config — no migration.
- Reuse existing audit-log storage.

## Error handling
- Backup/restore shell commands: capture exit code + stderr; surface friendly errors; never leave
  maintenance mode stuck (try/finally `artisan up`).
- Missing `mysqldump`/`pg_dump` binary → diagnostics flags it; backup returns actionable error.
- Restore validates manifest driver matches the current connection driver before importing.

## Testing
- `tests/Feature/SuperAdmin/AccessControlTest` — non-super-admin blocked from `/super-admin/*`;
  super-admin redirected correctly on login.
- `DiagnosticsTest` — endpoint returns expected health keys.
- `BackupRestoreTest` — sqlite round-trip: create backup, mutate data, restore, assert restored.
- `TenantDrillDownTest` — scoping correct, counts accurate.
- Run existing `tests/Feature/Admin/*` after route relocation; update any broken route names.

## Rollout / sequencing
Implement Phase 1 → review → Phase 2 → review → Phase 3. Each phase ends green (its tests + the
existing suite pass) before the next begins.

## Open risks
- Route relocation may break existing links/tests referencing `admin.tenants.*` etc. — mitigated by
  name-aliased redirects and a grep sweep of `route('admin.tenants` / `/admin/tenants` in `.vue`.
- `mysqldump` availability depends on host; diagnostics surfaces this; restore guarded by manifest
  driver check.
