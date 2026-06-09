# EventPro — Admin & Super Admin Login Reference

> **Internal support document.** Contains privileged credentials. Do not commit to public
> branches, share externally, or include in client-facing material. These are **seeded
> development/demo accounts** — rotate or remove before any production deployment.

_Last generated: 2026-06-09_

---

## Super Admin (platform-level)

Platform operator account. Has `tenant_id = NULL` and spans all tenants (impersonation,
suspension, audit log, platform settings).

| Field | Value |
|-------|-------|
| Name | Super Admin |
| Email | `admin@eventpro.io` |
| Password (seeder default) | `password` |
| Password (current, per ops note) | `Admin@123` |
| Role | `super_admin` |
| Tenant | none (platform scope) |
| Source | `database/seeders/UserSeeder.php` |

> The seeder sets the password to `password`. A later manual reset changed it to
> `Admin@123`. Try `Admin@123` first; if it fails, re-run the seeder or reset via tinker.

### Login gotcha (single-tenancy)

With `TENANCY_MODE=single`, a tenant is "current" even on `/login`. The null-tenant super
admin is found via the tenant-agnostic auth driver (`eloquent-tenantless`). If super admin
login ever returns "credentials do not match", verify `config/auth.php` →
`providers.users.driver = eloquent-tenantless` (see `app/Auth/TenantlessUserProvider.php`).

---

## Tenant Admins & Staff (tenant: **Mangala Events (Pvt) Ltd**, slug `grand-vista`)

Tenant domain: `mangala.localhost`

| Name | Email | Password | Role | Source |
|------|-------|----------|------|--------|
| Nuwan Perera | `nuwan@mangala.lk` | `password` | `admin` | `UserSeeder.php` |
| Sanduni Fernando | `sanduni@mangala.lk` | `password` | `staff` | `UserSeeder.php` |
| Kasun Rajapaksha | `admin@demo.eventpro.test` | `password` | `admin` | `DemoDataSeeder.php` |

All tenant accounts use password **`password`** as seeded.

---

## Re-seeding / resetting

```bash
# Re-run all seeders (recreates the users above, resets passwords to seeder defaults)
php artisan db:seed

# Just the user accounts
php artisan db:seed --class=Database\\Seeders\\UserSeeder

# Manual password reset via tinker (example: super admin)
php artisan tinker
>>> $u = \App\Models\User::withoutGlobalScope('tenant')->where('email','admin@eventpro.io')->first();
>>> $u->password = \Illuminate\Support\Facades\Hash::make('NewPassword123'); $u->save();
```

> Note: `BelongsToTenant::creating` auto-fills `tenant_id` from the current tenant. Create
> platform (null-tenant) users only when NO tenant is current (e.g. CLI/tinker).

---

## Summary

| Account | Email | Password | Scope |
|---------|-------|----------|-------|
| Super Admin | `admin@eventpro.io` | `Admin@123` / `password` | Platform |
| Tenant Admin | `nuwan@mangala.lk` | `password` | Mangala Events |
| Demo Admin | `admin@demo.eventpro.test` | `password` | Mangala Events |
| Staff | `sanduni@mangala.lk` | `password` | Mangala Events |
