# EventPro — Wedding & Event Management System

**A white-label, multi-tenant SaaS platform for managing weddings, corporate events,
conferences, and social functions.** One deployment serves many event businesses (tenants);
each gets its own branded admin area, staff, clients, and a customer self-service portal.

Built with **Laravel 12 · PHP 8.2+ · Vue 3 + Inertia.js · Tailwind CSS 3 · SQLite/MySQL**.

> 📋 **Presenting this project?** See the [Demonstration Guide](docs/DEMONSTRATION_GUIDE.md).
> 🔌 **Running offline / preparing for an evaluation?** See the [Offline Demo & Recovery README](DEMO-README.md).

---

## Table of Contents

1. [What it does](#what-it-does)
2. [Tech stack](#tech-stack)
3. [Architecture](#architecture)
4. [Quick start (local)](#quick-start-local)
5. [Running the app](#running-the-app)
6. [Login credentials](#login-credentials)
7. [User flows](#user-flows)
8. [Project structure](#project-structure)
9. [Developer workflow](#developer-workflow)
10. [Testing](#testing)
11. [Offline demo & evaluation recovery](#offline-demo--evaluation-recovery)
12. [Documentation](#documentation)
13. [Deployment](#deployment)

---

## What it does

Three audiences, three entry points:

| Audience | Area | Entry |
| --- | --- | --- |
| Platform operator | Super-admin console | `/admin` as super admin |
| Event-company team | Tenant admin area | `/admin` as tenant owner/admin/manager/staff |
| End customers | Client portal | `/portal` (self-registered at `/register`) |

### Core modules

| Module | What it covers |
| --- | --- |
| **Venues** | Multi-venue management, capacity, base pricing, availability |
| **Packages** | Modular package builder with included services and guest-based pricing |
| **Inquiries** | Public inquiry capture → normalized client + lead |
| **Quotations** | Quote builder, status workflow (draft → sent → accepted/rejected/expired), PDF + print |
| **Bookings** | Full booking lifecycle, availability checks, edit/confirm/cancel, unified calendar |
| **Payments** | Manual (cash/bank/cheque/card) + online (PayHere) installments, receipts (PDF), reminders |
| **Clients** | 360° client profiles and history |
| **Vendors** | External vendor directory and per-booking assignment |
| **Staff & Tasks** | Team management and event task assignment |
| **Reports** | Revenue/booking analytics with date ranges and PDF export |
| **Notifications** | In-app bell + best-effort email (mailables) |
| **Settings** | Per-tenant branding, payment config, custom fields |

### Platform (super-admin) features

Tenant management, plans, **impersonation** ("login as"), suspend/activate, **audit log**
(spatie/activitylog), platform settings, and cross-tenant user management.

### White-label & customization

Per-tenant branding (logo, favicon, accent colour via `BrandingService`), custom fields on
entities, theme engine, plugin scaffolding, multi-currency, and i18n support.

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Language / Framework | PHP 8.2+ · Laravel 12 |
| Admin SPA | Vue 3 + Inertia.js |
| Public site | Blade |
| Styling | Tailwind CSS 3 (self-hosted Inter + Fraunces fonts — offline-safe) |
| Build | Vite 6 |
| Calendar | FullCalendar 6 |
| Auth | Laravel Sanctum + Spatie Permission (roles/permissions) |
| Multi-tenancy | spatie/laravel-multitenancy (single / column / database modes) |
| Settings / Audit / Media | spatie/laravel-settings · activitylog · medialibrary |
| PDF / Excel / Images | barryvdh/laravel-dompdf · maatwebsite/excel · intervention/image |
| Database (demo) | SQLite — a single file, zero setup |
| Database (prod) | MySQL 8 / MariaDB |
| Payments | PayHere gateway (sandbox/live) |

The exact dependency versions live in [`composer.json`](composer.json) and [`package.json`](package.json).

---

## Architecture

### Request flow

```
HTTP → route (routes/web.php, api.php) → middleware (auth, SetCurrentTenant,
       permission:*, EnsureTenantActive) → Controller → Service (business logic)
       → Model (Eloquent, tenant-scoped) → Inertia::render(...) → Vue page
```

- **Controllers** are thin: validate, call a service, return an Inertia page or redirect.
- **Services** (`app/Services/`) hold the logic: `AvailabilityService`, `PricingService`,
  `PaymentService`, `PayHereService`, `QuotationService`, `BrandingService`, `SettingsService`, …
- **Models** (`app/Models/`) — 15 entities (Venue, Package, Booking, Quotation, Payment,
  Client, Inquiry, Vendor, Staff, Task, Tenant, User, Plan, CustomField, …). Most use the
  `BelongsToTenant` trait, which adds a global scope so every query is automatically limited
  to the current tenant, and stamps `tenant_id` on create.

### Multi-tenancy

`TENANCY_MODE` (in `.env`) selects the strategy:

- **single** *(default for this build)* — one database, the current tenant is resolved from
  the authenticated user by the `SetCurrentTenant` middleware.
- **column** — one shared database, rows scoped by `tenant_id` (SaaS with many tenants).
- **database** — a separate database per tenant (enterprise isolation).

The **super admin** has `tenant_id = NULL` and spans all tenants; it authenticates via a
tenant-agnostic provider (`eloquent-tenantless`).

### Roles

Six roles seeded by `RolePermissionSeeder`: `super_admin`, `tenant_owner`, `admin`,
`manager`, `staff`, `client`. Access is enforced with `permission:*` / `role:*` middleware.

---

## Quick start (local)

**Prerequisites:** PHP 8.2+ (with `pdo_sqlite`, `mbstring`, `openssl`, `gd`, `zip`),
Composer, and Node.js 18+. On Windows, [Laravel Herd](https://herd.laravel.com/) provides
PHP + Composer in one install.

```bash
# 1. Clone
git clone https://github.com/AshenWijesingha/Wedding-and-Event-Management-System.git
cd Wedding-and-Event-Management-System

# 2. PHP + JS dependencies
composer install
npm install

# 3. Environment
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

#    Use the zero-setup SQLite database (recommended for local/demo).
#    In .env set:  DB_CONNECTION=sqlite   and comment out the DB_HOST/PORT/DATABASE/... lines.
#    Then create the file:
#      bash:        touch database/database.sqlite
#      powershell:  New-Item database/database.sqlite

# 4. Migrate + seed demo data (venues, packages, bookings, users, two tenants)
php artisan migrate --seed

# 5. Build front-end assets
npm run build

# 6. Serve
php artisan serve            # http://127.0.0.1:8000
```

Prefer MySQL or Docker? Keep the `DB_*` values from `.env.example` and see
[`docs/installation.md`](docs/installation.md) and [`DOCKER.md`](DOCKER.md).

> **Windows one-click:** double-click **`Start-EventPro.exe`** in the project root — it finds
> PHP, skips install steps when dependencies already exist, seeds SQLite on first run, serves
> the app, and opens the browser. (Build it from source with `launcher\build.cmd`.)
> A `setup.bat` (winget-based, **needs internet**) can install PHP/Node/MariaDB from scratch.

---

## Running the app

| Command | Purpose |
| --- | --- |
| `php artisan serve` | Run the app at `http://127.0.0.1:8000` |
| `npm run dev` | Vite dev server with hot reload (for active front-end work) |
| `npm run build` | Production asset build into `public/build/` |
| `php artisan migrate:fresh --seed` | Reset the database to clean seeded demo data |
| `php artisan test` | Run the test suite (isolated in-memory DB) |

For day-to-day front-end work run `php artisan serve` **and** `npm run dev` together. For a
demo or evaluation, run only `php artisan serve` against the production build (no dev server).

---

## Login credentials

All seeded passwords are **`password`** (development/demo accounts only — rotate before production).

| Role | Email | Notes |
| --- | --- | --- |
| Super admin | `admin@eventpro.io` | Platform scope. Password may have been reset to `Admin@123` — try that if `password` fails. |
| Tenant admin | `nuwan@mangala.lk` | Tenant: *Mangala Events* |
| Tenant admin | `admin@demo.eventpro.test` | Curated demo tenant |
| Staff | `sanduni@mangala.lk` | Limited permissions |
| Data-rich tenant | `owner@showcase.eventpro.test` | Tenant owner of the showcase tenant (lots of seeded entities) |
| Client | — | Register a new account at `/register` (no seeded client login) |

Reset to seeder defaults any time with `php artisan db:seed`. Full reference:
[`docs/ADMIN-LOGINS.md`](docs/ADMIN-LOGINS.md).

---

## User flows

### Public visitor → lead
1. Browse the public site (`/`) — venues, packages, gallery, contact.
2. Submit an **inquiry** → creates a normalized `Client` + `Inquiry` for the tenant.
3. The tenant team is notified (in-app + best-effort email).

### Sales pipeline (tenant admin)
1. **Inquiry** lands in `/admin/inquiries`.
2. Convert to a **Quotation** (prefilled from the inquiry) → send → client accepts.
3. Accepted quote becomes a **Booking** (availability checked via `AvailabilityService`).
4. Record **Payments** (manual installments or online via PayHere) → receipts (PDF),
   automatic reminders, balance tracking.
5. Assign **vendors**, **staff**, and **tasks**; track everything on the unified **calendar**.
6. Booking moves through `confirmed` → `completed` (or `cancelled`).

### Customer (client portal)
1. Register at `/register` → auto-login to `/portal`.
2. View bookings, quotations, and balances; **pay online** (PayHere) and download receipts.

### Platform operator (super admin)
Manage tenants and plans, **impersonate** a tenant ("login as"), suspend/activate accounts,
review the **audit log**, and configure platform settings — all from `/admin`.

---

## Project structure

```
app/
  Console/Commands/      Artisan commands (incl. dev:doctor / dev:baseline / dev:restore)
  Http/Controllers/      Admin/  Portal/  (+ public) controllers
  Http/Middleware/       SetCurrentTenant, EnsureTenantActive, SecurityHeaders, …
  Models/                15 Eloquent models (BelongsToTenant trait)
  Services/              Business logic (pricing, availability, payments, branding, …)
config/                  Framework + eventpro config
database/
  migrations/  seeders/  factories/   database.sqlite (demo DB)
resources/
  js/Pages/              Vue 3 + Inertia pages (one folder per module)
  js/Layouts/ Components/ AppLayout, PortalLayout, shared UI
  views/                 Blade: app shell, public site, PDF + mail templates
  css/  fonts/           Tailwind entry + self-hosted woff2 fonts (offline)
routes/                  web.php · api.php · console.php
tests/                   Feature + Unit (run against in-memory SQLite)
tools/demo/              Git-free integrity doctor (see DEMO-README.md)
docs/                    Guides (installation, demo, admin logins, recovery, API)
```

---

## Developer workflow

```bash
composer install && npm install     # dependencies
php artisan migrate:fresh --seed    # clean demo data
npm run dev                         # Vite HMR  (separate terminal)
php artisan serve                   # app server

vendor/bin/pint                     # auto-format (Laravel Pint)
vendor/bin/phpstan analyse          # static analysis (larastan, level 5)
php artisan test                    # full suite
```

CI (`.github/workflows/`) runs Pint, PHPStan, and the PHP 8.4 test suite on every PR.

---

## Testing

```bash
php artisan test                    # all tests
php artisan test --filter Booking   # a subset
```

Tests run against an **in-memory SQLite** database (configured in `phpunit.xml`), so they
never touch your demo data. The suite covers public pages, the inquiry→quotation→booking→
payment pipeline, every admin section, the client portal, multi-tenant isolation, and
security headers.

---

## Offline demo & evaluation recovery

This repo ships a complete **offline + git-free** workflow — see [`DEMO-README.md`](DEMO-README.md):

- **Runs with no internet:** self-hosted fonts, SQLite, file cache, sync queue, log mail.
- **`dev doctor`** — pinpoints exactly which files/lines were changed or deleted versus a
  captured baseline (no git needed), and runs every CRUD test. `dev baseline` captures the
  known-good state; `dev restore <path>` recovers from it. Works even if the app can't boot.
  (In PowerShell run `.\dev doctor`; `php artisan dev:doctor` works in any shell.)
- **`tools\demo\backup.cmd`** — zips the whole project (incl. `vendor/`, `node_modules/`) as
  a full safety net.

---

## Documentation

| Doc | Contents |
| --- | --- |
| [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) | **Exhaustive method-level guide** — every function, call chains, full file map, viva Q&A |
| [DEMO-README.md](DEMO-README.md) | Offline run + git-free recovery tool (one-stop) |
| [docs/installation.md](docs/installation.md) | Full install (SQLite, MySQL, Docker, production) |
| [docs/DEMONSTRATION_GUIDE.md](docs/DEMONSTRATION_GUIDE.md) | Scene-by-scene demo script |
| [docs/OFFLINE_DEMO.md](docs/OFFLINE_DEMO.md) | Running completely offline |
| [docs/EVALUATION_RECOVERY.md](docs/EVALUATION_RECOVERY.md) | Recovery playbook + architecture map |
| [docs/ADMIN-LOGINS.md](docs/ADMIN-LOGINS.md) | Seeded credentials |
| [docs/admin-guide.md](docs/admin-guide.md) · [docs/developer-guide.md](docs/developer-guide.md) | Admin & developer guides |
| [docs/api.md](docs/api.md) | REST API reference |
| [DOCKER.md](DOCKER.md) | Docker setup |

---

## Deployment

Production runs on PHP 8.2+ with MySQL/MariaDB, Redis (cache/queue), and a real web server
(nginx/Apache) serving `public/`. Outline:

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:work        # run via a supervisor
```

Set production drivers in `.env` (MySQL, Redis, real mailer), a strong `APP_KEY`, and
`APP_ENV=production` / `APP_DEBUG=false`. Full steps and the queue/scheduler setup are in
[`docs/installation.md`](docs/installation.md).

---

## License

Proprietary. © EventPro. All rights reserved.
