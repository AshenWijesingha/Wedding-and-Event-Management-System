# Evaluation Recovery Playbook

Some evaluations **delete or alter a piece of code and ask you to fix it live**.
This playbook makes that safe — **offline and without git**. The goal is not to hide
the change: it is to **see exactly which file and which lines changed**, prove the
rest of the system still behaves *as before*, and reinstate the missing code (by hand,
or with one command).

It is built on a **git-free baseline**: a manifest of sha256 hashes plus a verbatim
copy of every source file, captured while the code is healthy. The tooling compares
the working tree against that baseline by hashing — no git, no internet.

> Engine: `tools/demo/lib/Integrity.php` (plain PHP, runs even when the app cannot
> boot). Commands: `php artisan dev:baseline|dev:doctor|dev:restore`, or the
> `dev` / `tools\demo\*.cmd` wrappers.

---

## One-time setup (before the evaluation, on healthy code)

```cmd
dev baseline
:: -> captures every source file into tools/demo/baseline/ (hash + copy)
```

Optionally also make a full artifact backup (covers the git-ignored `vendor/`,
`node_modules/`, `public/build/`, `.env`):

```cmd
tools\demo\backup.cmd
:: -> %USERPROFILE%\EventPro-Backup\eventpro-demo-<timestamp>.zip  (copy to USB too)
```

---

## The live loop (when code is deleted/changed)

```
 1. dev doctor                 ->  WHAT changed (files + exact lines) and which CRUD flow broke
 2. fix it:
      (a) re-type the missing/changed code yourself, using the line list as the key  <- preferred
      (b) dev restore <path>   ->  recover that one file from the baseline
 3. dev doctor                 ->  confirm HEALTHY
```

What `dev doctor` shows:

- **Source integrity vs baseline** — `DELETED` files, `MODIFIED` files with the
  precise `- line` (baseline) / `+ line` (current) changes, and `ADDED` files. When
  everything matches, it says *"All functions/CRUD code is identical to baseline."*
- **Offline readiness** — no external font/CDN link re-introduced, built assets
  present, `.env` on offline drivers, no stale `public/hot`.
- **Functional suite** — runs the test suite so every CRUD path is exercised end-to-end.

Useful variants:

```cmd
dev doctor --no-tests                 :: fast: just the change list, skip the test run
dev restore app\Http\Controllers\Admin\VenueController.php   :: one file
dev restore --all                     :: restore every missing/modified file
php artisan dev:doctor                :: same as `dev doctor` (artisan form)
php tools\demo\integrity.php --check  :: pure git-free check, works even if the app won't boot
```

If a **git-ignored artifact** is what got deleted (`vendor/`, `node_modules/`,
`public/build/`, `.env`), restore it from the `backup.cmd` zip — those are not part
of the source baseline. To rebuild from scratch *online*: `composer install`,
`npm install && npm run build`, `php artisan migrate:fresh --seed`.

---

## Architecture map — where things live (so you can hand-fix)

EventPro is Laravel 12 + Vue 3 + Inertia. A request flows:
**route → middleware → controller → service → model → (Inertia) Vue page**.

| You need to fix... | Look in | Notes |
| --- | --- | --- |
| A **route** (404 / missing endpoint) | `routes/web.php`, `routes/api.php`, `routes/console.php` | Admin routes grouped with `auth` + `permission:*`. `php artisan route:list` lists them. |
| A **controller** action | `app/Http/Controllers/` (admin in `Admin/`, client in `Portal/`, public at top level) | Thin: validates, calls a service, returns `Inertia::render(...)` or a redirect. |
| **Business logic** (pricing, availability, payments, branding, quotations, settings) | `app/Services/` | `AvailabilityService`, `PaymentService`, `PayHereService`, `BrandingService`, `QuotationService`, `PricingService`, `SettingsService`. |
| A **model** / relationship / cast | `app/Models/` | `Booking`, `Venue`, `Package`, `Client`, `Inquiry`, `Quotation`, `Payment`, `Tenant`, `User`, `CustomField`. Most use the `BelongsToTenant` trait (tenant global scope). |
| **Validation rules** | the controller's `$request->validate([...])`, plus `app/Support/TenantRule.php` for tenant-scoped `exists`. | |
| **Permissions / roles** | middleware aliases + `database/seeders/RolePermissionSeeder.php` (Spatie). | Tenant resolved by `SetCurrentTenant` middleware. |
| **Security headers / CSP** | `app/Http/Middleware/SecurityHeaders.php` | Fonts are `'self'`; do **not** re-add external CDNs (breaks offline). |
| A **Vue page / component** | `resources/js/Pages/` (one folder per area), `resources/js/Layouts/`, `resources/js/Components/` | Props come from the controller's `Inertia::render`. After editing, `npm run build`. |
| **Front-end styling / fonts** | `resources/css/app.css`, `resources/css/fonts.css`, `tailwind.config.js` | Self-hosted woff2 in `resources/fonts/`. |
| **Blade shells / public site** | `resources/views/app.blade.php` (SPA shell), `resources/views/layouts/app.blade.php` (public site), `resources/views/pdf/*`, `resources/views/mail/*` | |
| **Seed / demo data** | `database/seeders/` + `database/seeders/Data/` | Re-seed: `php artisan migrate:fresh --seed`. |
| **Migrations / schema** | `database/migrations/` | |
| **Tests** (prove your fix) | `tests/Feature/` | `php artisan test`, or `dev doctor` runs them for you. |

### Quick fault → file shortcuts
- *Page blank / 500 right after a deletion* → run `dev doctor`; the `DELETED`/`MODIFIED`
  list points straight at it.
- *"Class/method not found"* → a controller or service method was removed →
  `app/Http/Controllers/` or `app/Services/`.
- *Route 404* → `routes/web.php`.
- *Form rejects valid input / accepts invalid* → the controller's `validate([...])`.
- *Styling/fonts gone* → rebuild (`npm run build`); check `resources/css/`.
- *A whole flow fails* → the functional suite in `dev doctor` names the failing test;
  open it to see which route/flow it exercises.

---

## Golden rules during evaluation

- **`dev doctor` first.** Don't guess — it shows the exact files and lines that changed.
- Prefer **re-implementing by hand** (you learn, and it answers the examiner); keep
  `dev restore` as the safety net if you run out of time.
- After any fix, **`dev doctor`** again; if you touched the front-end, `npm run build`.
- Never re-add an external font/CDN `<link>` — it silently breaks the offline demo.

> The git-free baseline is the primary mechanism. If you also use git, a
> `demo-baseline` tag and `git diff` work as an additional cross-check — but nothing
> here depends on git.
