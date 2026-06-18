# EventPro — Demonstration Guide

A step-by-step script for presenting **EventPro** (Wedding & Event Management System)
to the project supervisor. Every flow below is covered by an automated readiness
test (`tests/Feature/DemoReadinessTest.php`) and verified working end-to-end.

---

## 1. What you are presenting

EventPro is a **multi-tenant, white-label SaaS** for wedding and event management
companies. One deployment serves many event businesses (tenants); each gets its own
branded admin area, staff, clients, and a client self-service portal.

**Three audiences, three entry points:**

| Audience | Area | Entry |
|----------|------|-------|
| Platform operator (you) | Super-admin console | `/admin` as super admin |
| Event company staff | Tenant admin area | `/admin` as tenant admin/manager/staff |
| End customers | Client portal | `/portal` (self-registered) |

**Stack:** Laravel 11 · PHP 8.2 · Vue 3 + Inertia.js · Tailwind CSS · SQLite (demo) /
MySQL (prod) · Spatie permissions + multitenancy · PayHere payment gateway (sandbox).

---

## 2. One-time setup (before the demo)

Run these from the project root. Takes ~1 minute.

```bash
# 1. PHP + JS dependencies (skip if already installed)
composer install
npm install

# 2. Fresh database with full demo data
php artisan migrate:fresh --seed

# 3. Build frontend assets
npm run build

# 4. Link storage for uploaded images/avatars
#    (safe to skip — "The link already exists" just means it's already done)
php artisan storage:link

# 5. Start the app
php artisan serve
```

Open **http://127.0.0.1:8000**.

> **Confidence check (optional, ~45s):** `php artisan test --filter=DemoReadinessTest`
> walks every flow in this guide against the real seeded data. All green = the live
> demo will work. The full suite is **296 passing tests**.

---

## 3. Login credentials

All passwords are **`password`**.

| Role | Email | Lands on | Use it to show |
|------|-------|----------|----------------|
| **Super admin** | `admin@eventpro.io` | Super-admin console | Platform-wide controls |
| **Tenant admin** (Mangala Events — curated hero story) | `admin@demo.eventpro.test` | Tenant dashboard | Day-to-day operations |
| Tenant admin (alt) | `nuwan@mangala.lk` | Tenant dashboard | Second admin |
| Staff (read-mostly) | `sanduni@mangala.lk` | Tenant dashboard | Permission gating |
| **Showcase tenant** (10+ of every entity) | `admin@showcase.eventpro.test` | Tenant dashboard | Data-rich screens, reports, calendar |
| **Client** | *self-register live* — see Part 2 | Client portal | New-customer journey |

> Use **Mangala** (`admin@demo.eventpro.test`) for the storytelling flow and
> **Showcase** (`admin@showcase.eventpro.test`) when you want busy, data-heavy screens
> (calendar, reports). There is **no pre-seeded client login** — registering one live
> is part of the demo (Part 2) and shows the new-account flow working.

---

## 4. Demonstration script

> Tip: present in this order — it follows a real customer's journey, from public
> website → self sign-up → the company managing the event → the platform operator.

### Part 1 — Public website (no login)

1. **Landing page** `/` — branded hero, value proposition.
2. **Venues** `/venues` → click a venue for its detail page (`/venues/{slug}`).
3. **Packages** `/packages` — pricing tiers.
4. **Contact / Inquiry** `/contact` — fill the inquiry form and submit.
   - **Say:** "This drops a lead straight into the company's inbox — watch, it'll
     appear in the admin Inquiries list and email the team."
5. `/links` — a hub listing every public page (handy during the demo).

### Part 2 — New customer self-registration → Client Portal  ⭐ (the new-account flow)

1. Go to `/register`. Fill name, email, password.
2. Submit → you are **logged in automatically and dropped at `/portal`**.
   - **Say:** "Registration created a `client` user *and* a linked client profile,
     and signed them in — zero manual steps."
3. Walk the portal:
   - `/portal` — dashboard.
   - `/portal/bookings` — the customer's events.
   - `/portal/quotations` — quotes sent to them (accept/decline).
   - `/portal/payments` — invoices + **Pay now** (PayHere sandbox checkout) + receipts.
4. Click the **notification bell** (top bar) to show in-app notifications.

> A freshly registered account has no bookings yet — that's expected. To show a
> *populated* portal, you can instead log in as the Mangala tenant admin (Part 3),
> create a booking + quotation for an existing client, then register/log in as that
> client. For most demos the empty-then-explained portal is enough.

### Part 3 — Event company admin (tenant)  ⭐ (the core product)

Log in as `admin@demo.eventpro.test`.

1. **Dashboard** `/admin` — live tenant stats (bookings, open inquiries, month
   revenue, clients), upcoming events, recent inquiries. First-run users see a
   **Getting started** checklist (onboarding wizard).
2. **Guided tour** — click the **?** button (bottom corner) to launch the spotlight
   tour for the page. (Auto-runs on first visit.)
3. **Inquiries → Quotation workflow** `/admin/inquiries`:
   - Open the inquiry submitted in Part 1.
   - **Convert to quotation** (prefills the quotation form).
   - In `/admin/quotations`: **Send → Accept/Reject** lifecycle; **Download PDF** and
     **Print** views.
4. **Bookings** `/admin/bookings`:
   - **Create** a booking (pick client, venue, package, date — availability is checked).
   - **Edit** it (the date re-check excludes itself, so saving the same date is fine).
   - **Confirm** it → the client is emailed + staff get an in-app notification.
   - **Cancel** (with reason). Attach **vendors**.
5. **Calendar** `/admin/calendar` — all bookings on a month/week/list calendar,
   color-coded by status; click an event to jump to the booking.
6. **Clients** `/admin/clients`:
   - **New client**, edit, and the **360° profile** (`Show`): their inquiries,
     quotations, bookings, payments, lifetime value, open balance.
7. **Payments** `/admin/payments`:
   - **Record a manual payment** against a booking → client emailed, balance recomputed.
   - **Download receipt** (PDF).
8. **Vendors / Staff / Tasks** — supporting CRUD (`/admin/vendors`, `/admin/staff`,
   `/admin/tasks`).
9. **Reports** `/admin/reports` — revenue/bookings by date range; **export PDF**.
10. **Settings** `/admin/settings`:
    - **Branding** (business name, primary color) — show that the accent updates
      app-wide (white-label).
    - **Payments** tab — PayHere gateway configuration (write-only secret).
11. **Notification bell** — point out the unread badge from the booking confirm / payment.

### Part 4 — Permission gating (quick, optional)

Log in as `sanduni@mangala.lk` (staff). Show that mutating buttons/nav (create
booking, settings, users) are hidden/blocked — staff is read-mostly. Server-side
policies enforce it, not just the UI.

### Part 5 — Super-admin platform console  ⭐

Log in as `admin@eventpro.io`.

1. **Tenants** `/admin/tenants`:
   - **Suspend / Activate** a tenant (suspended tenants are locked out — enforced by
     middleware).
   - **"Login as"** (impersonation) → you enter that tenant's admin area with a banner;
     **Exit impersonation** returns you.
2. **Plans** `/admin/plans` — subscription plans.
3. **Platform settings** `/admin/platform-settings` — platform name, default plan,
   **toggle public sign-ups**, support email.
4. **Audit log** `/admin/audit-log` — platform-wide activity (tenant/plan/user/quotation
   changes), powered by spatie/activitylog.
5. **Users** `/admin/users` — cross-tenant user management (super-admin only).

### Part 6 — Showcase data + extras (optional)

- Log in as `admin@showcase.eventpro.test` to show busy screens: a full calendar,
  populated reports, 10+ of every entity.
- **Public demo sandbox** (if `DEMO_MODE=true`): the landing page shows **"Try the
  demo"**, which spins up a private, throwaway tenant for a visitor — great for letting
  the supervisor click around safely.

---

## 5. Feature checklist (tick during the demo)

- [ ] Public site: landing, venues, packages, contact/inquiry, links hub
- [ ] **New user registration → auto-login → client portal**
- [ ] Client portal: dashboard, bookings, quotations, payments + PayHere + receipts, notifications
- [ ] Tenant dashboard with live stats + onboarding checklist
- [ ] Guided in-app tours
- [ ] Inquiry → quotation conversion + quotation lifecycle (send/accept/reject/expire) + PDF + print
- [ ] Bookings: create / edit / confirm / cancel / vendor attach / availability check
- [ ] Unified events calendar (month/week/list, status colors)
- [ ] Clients: CRUD + 360° profile (lifetime value, open balance, delete guard)
- [ ] Payments: manual record + gateway + receipt PDF + balance recompute
- [ ] Vendors / Staff / Tasks CRUD
- [ ] Reports with date range + PDF export
- [ ] Settings: branding (white-label) + PayHere config
- [ ] Email + in-app notifications (booking confirmed, payment, new inquiry, quotation, invite)
- [ ] Role-based permission gating (staff vs admin)
- [ ] Super admin: tenants (suspend/activate/impersonate), plans, platform settings, audit log, users
- [ ] Showcase tenant (data-rich) and/or public demo sandbox

---

## 6. Reset & troubleshooting

| Symptom | Fix |
|---------|-----|
| Want a clean slate mid-demo | `php artisan migrate:fresh --seed` (re-seeds all demo data + logins) |
| Page looks unstyled | `npm run build`, then delete `public/hot` if present, reload |
| "Public sign-ups are currently disabled" on `/register` | Super admin → Platform settings → enable sign-ups (default is **on**) |
| Emails | Default mailer is `smtp`/`log`; for the demo set `MAIL_MAILER=log` in `.env` and read mail in `storage/logs/laravel.log` — no real SMTP needed |
| Payments | PayHere runs in **sandbox**; no real money moves |
| Verify everything before presenting | `php artisan test` (296 pass) and `php artisan test --filter=DemoReadinessTest` |

---

## 7. Likely supervisor questions — talking points

- **Multi-tenancy:** every record is tenant-scoped via a `BelongsToTenant` global
  scope + tenant-resolution middleware; one tenant can never see another's data
  (covered by isolation tests).
- **Security:** role/permission gates on every action (Spatie), CSRF, a tenant-scoped
  `exists` validation rule, fixed cross-tenant IDOR + branding XSS, session timeout.
- **White-label:** only the accent color comes from tenant branding; neutral/status
  colors are fixed design tokens, so every tenant stays on-brand and legible.
- **Quality:** 296 automated tests, clean production build, one-command seed for a
  reproducible demo.
- **Notifications are best-effort:** a mail failure is logged, never breaks the user's
  action.

---

*Generated and verified on branch `main`. Readiness proof: `tests/Feature/DemoReadinessTest.php`.*
