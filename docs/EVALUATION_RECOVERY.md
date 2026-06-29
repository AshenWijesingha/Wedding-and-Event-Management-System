# Evaluation Recovery Playbook

Some evaluations **delete or alter a piece of code and ask you to fix it live**.
This playbook makes that safe. The goal is not to hide the change — it is to let
you **see exactly what was removed and reinstate or re-learn it quickly**, with a
guaranteed restore point as a fallback.

It rests on three things:

1. **`demo-baseline`** — a git tag pinned to a known-good commit of the whole
   source tree.
2. **A full backup `.zip`** — covers the artifacts git does *not* track
   (`vendor/`, `node_modules/`, `public/build/`, `.env`). The seeded
   `database/database.sqlite` *is* tracked in git, so the baseline tag already
   covers it.
3. **`tools/demo/` scripts** — `doctor`, `diff`, `restore`, `backup`.

---

## One-time setup (before the evaluation)

```cmd
:: 1. Make sure you are on a healthy, committed checkout, then tag it:
git tag -a demo-baseline -m "known-good offline demo state"

:: 2. Make the full safety-net archive (includes vendor, node_modules, build, db):
tools\demo\backup.cmd
:: -> %USERPROFILE%\EventPro-Backup\eventpro-demo-<timestamp>.zip
:: Copy that .zip to a USB stick as well.
```

> If you later make legitimate improvements and want them to be the new baseline:
> `git tag -d demo-baseline && git tag -a demo-baseline -m "..."`, then re-run
> `backup.cmd`.

---

## The live loop (when code is deleted/changed)

```
 1. tools\demo\doctor.cmd     ->  is anything broken, and roughly where?
 2. tools\demo\diff.cmd       ->  exactly which lines/files were removed?
 3. fix it:
      (a) re-type the code yourself, using the diff as the answer key   <- preferred
      (b) tools\demo\restore.cmd <path>   ->  recover that one file
 4. tools\demo\doctor.cmd     ->  confirm HEALTHY again
```

- `doctor.cmd` runs a file checklist, boots the route table, checks no internet
  font link crept back, and runs the **22-check** `DemoReadinessTest`. Each line is
  `[OK]` / `[FAIL]` / `[MISSING]`, so a break is localised in seconds.
- `diff.cmd` shows `git status` plus the **full diff against `demo-baseline`**.
  Deleted lines start with `-`; that is precisely what you need to put back.
- `restore.cmd path\to\File.php` restores one file from the baseline;
  `restore.cmd` with no argument restores **all** tracked files (it asks first).
- If a git-**ignored** artifact was deleted (`vendor/`, `node_modules/`,
  `public/build/`, `.env`), restore it from the backup `.zip` instead — git never
  tracked those. (The sqlite DB *is* tracked, so `restore.cmd` recovers it too.)
  To rebuild from scratch *online*:
  `composer install`, `npm install && npm run build`,
  `php artisan migrate:fresh --seed`.

---

## Architecture map — where things live (so you can hand-fix)

EventPro is Laravel 12 + Vue 3 + Inertia. A request flows:
**route → middleware → controller → service → model → (Inertia) Vue page**.

| You need to fix... | Look in | Notes |
| --- | --- | --- |
| A **route** (404 / missing endpoint) | `routes/web.php`, `routes/api.php`, `routes/console.php` | Admin routes are grouped with `auth` + `permission:*` middleware. `php artisan route:list` shows them all. |
| A **controller** action | `app/Http/Controllers/` (admin in `Admin/`, client in `Portal/`, public at top level) | Thin: validates, calls a service, returns `Inertia::render(...)` or a redirect. |
| **Business logic** (pricing, availability, payments, branding, quotations, settings) | `app/Services/` | e.g. `AvailabilityService`, `PaymentService`, `PayHereService`, `BrandingService`, `QuotationService`, `PricingService`, `SettingsService`. |
| A **model** / relationship / cast | `app/Models/` | `Booking`, `Venue`, `Package`, `Client`, `Inquiry`, `Quotation`, `Payment`, `Tenant`, `User`, `CustomField`. Most use the `BelongsToTenant` trait (tenant global scope). |
| **Validation rules** | the controller's `$request->validate([...])`, plus `app/Support/TenantRule.php` for tenant-scoped `exists`. | |
| **Permissions / roles** | `app/Http/Middleware/` aliases + `database/seeders/RolePermissionSeeder.php` (Spatie). | Tenant resolved by `SetCurrentTenant` middleware. |
| **Security headers / CSP** | `app/Http/Middleware/SecurityHeaders.php` | Fonts are `'self'`; do **not** re-add external CDNs (breaks offline). |
| A **Vue page / component** | `resources/js/Pages/` (one folder per area), `resources/js/Layouts/` (`AppLayout`, `PortalLayout`), `resources/js/Components/` | Page props come from the controller's `Inertia::render`. After editing, `npm run build`. |
| **Front-end styling / fonts** | `resources/css/app.css`, `resources/css/fonts.css`, `tailwind.config.js` | Self-hosted woff2 live in `resources/fonts/`. |
| **Blade shells / public site** | `resources/views/app.blade.php` (SPA shell), `resources/views/layouts/app.blade.php` (public site), `resources/views/pdf/*`, `resources/views/mail/*` | |
| **Seed / demo data** | `database/seeders/` (`DatabaseSeeder`, `TenantSeeder`, `DemoDataSeeder`, `RolePermissionSeeder`) and `database/seeders/Data/` | Re-seed with `php artisan migrate:fresh --seed`. |
| **Migrations / schema** | `database/migrations/` | |
| **Tests** (use these to prove your fix) | `tests/Feature/`, esp. `DemoReadinessTest.php` | `php artisan test` or target one file. |

### Quick fault → file shortcuts
- *Page is blank / 500 right after deletion* → the deleted thing is referenced
  somewhere; run `diff.cmd` and read the `-` lines.
- *"Class/method not found"* → a controller or service method was removed →
  `app/Http/Controllers/` or `app/Services/`.
- *Route 404* → `routes/web.php`.
- *Form rejects valid input / accepts invalid* → the controller's `validate([...])`.
- *Styling/fonts gone* → rebuild (`npm run build`); check `resources/css/`.
- *A whole flow fails* → `doctor.cmd` names the failing `DemoReadinessTest` case;
  open that test to see which route/flow it exercises.

---

## Golden rules during evaluation

- **`doctor` first, `diff` second, fix third.** Don't guess — let the diff show you.
- Prefer **re-implementing by hand** (you learn, and it answers the examiner);
  keep `restore.cmd` as the safety net if you run out of time.
- After any fix, **`doctor` again** and, if you touched the front-end,
  `npm run build`.
- Never re-add an external font/CDN `<link>` — it silently breaks the offline demo.
