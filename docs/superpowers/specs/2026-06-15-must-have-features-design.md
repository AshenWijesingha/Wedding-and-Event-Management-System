# Must-Have Missing Features — Design Spec

> Status: APPROVED (2026-06-15). Four independent sub-projects built sequentially,
> each TDD, full suite green + `npm run build` clean before the next.
> Build order: SP-2 Client CRUD → SP-3 Booking edit → SP-4 Calendar → SP-1 Email/notifications.

Date: 2026-06-15
Branch: `feat/ui-optimization` (continues the current branch)

## Goal

Fill the genuine must-have gaps that block everyday operational use of EventPro.
The app already has full CRUD for venues, packages, bookings (partial), vendors,
staff, tasks, inquiries, quotations, payments, reports, settings, users, tenants,
plans, the portal, onboarding, tours, and the demo sandbox. This work closes the
remaining holes. Global omnibox search is explicitly out of scope for now.

All entities are tenant-scoped via `BelongsToTenant`. Every controller action is
gated by the existing Spatie permission pattern. Tests use `RefreshDatabase`,
`Mail::fake()` / `Notification::fake()` where relevant.

## SP-1 — Email + Notifications

### Transactional email (`app/Mail/`, Markdown mailables)

| Mailable | Trigger | Recipient |
|----------|---------|-----------|
| `QuotationSentMail` | `QuotationController@send` | quotation client |
| `BookingConfirmedMail` | `BookingController@confirm` | booking client |
| `PaymentReceivedMail` | `PaymentService::applyGatewayResult` + manual `recordManual` (completed) | payment client |
| `UserInvitedMail` | `UserController@store`, `OnboardingController@invite` | new user (temp password / set-password link) |
| `NewInquiryMail` | `InquiryController@store` (public) | tenant staff inbox address |

- Mailables implement `ShouldQueue`; `QUEUE_CONNECTION=sync` by default so no
  worker is required for dev/everyday. Prod can switch the connection.
- Use existing tenant email-template settings where present (subject/body), else
  sensible defaults. Branding (tenant name, primary color) in the layout.
- Failures must not break the originating request: wrap dispatch so a mail error
  is logged, not fatal (notifications are best-effort side effects).

### In-app notifications (Laravel `database` channel)

- `php artisan notifications:table` migration (`notifications` table).
- Staff-facing notifications on: new inquiry, booking confirmed, payment received,
  quotation accepted/rejected. Stored against the relevant tenant users.
- Bell component in `AppLayout` topbar: unread count badge, dropdown of recent,
  "mark all read" + per-item read. Shared to Inertia via `HandleInertiaRequests`
  (recent + unread count for the auth user).
- Admin read endpoint mirrors the existing portal `notifications.read` route:
  `POST /admin/notifications/read` (+ optional id) → marks read.
- Reuse the existing `SendPaymentReminders` command; register it on the scheduler
  (daily) in `routes/console.php` / `bootstrap/app.php` schedule.

### Tests
`Mail::fake()` asserts each mailable is queued to the right recipient on its
trigger. `Notification::fake()` asserts staff DB notifications. A request whose
mail send throws still returns success (best-effort).

## SP-2 — Client CRUD + 360° profile

- Extend `ClientController` with `show/create/store/edit/update/destroy`
  (currently `index` only). Add routes under the existing `clients.view` group,
  with `clients.create` / `clients.edit` / `clients.delete` permission middleware
  on the mutating actions (add these permissions to `RolePermissionSeeder`).
- `ClientPolicy` mirroring existing policies; register in `AuthServiceProvider`.
- Validation via a `ClientRequest` FormRequest (name, email unique per tenant,
  phone, address, notes, custom fields if applicable).
- **Vue pages:** `Clients/Create`, `Clients/Edit`, `Clients/Show`. Index gets
  row actions (view/edit/delete) + "New client" button.
- **`Show` = 360° profile:** client details card + sections for the client's
  inquiries, quotations, bookings, and payments (eager-loaded, tenant-scoped),
  each linking to the respective detail page. Totals: lifetime value (sum of
  completed payments), open balance.
- **Delete guard:** if the client has any bookings or quotations, block the
  delete with a clear flash error (no orphaned records). Otherwise hard delete.

### Tests
Feature tests: index/show/create/store/edit/update/destroy happy paths +
permission gating + tenant isolation + delete-guard when related records exist +
email-unique-per-tenant validation.

## SP-3 — Booking edit / reschedule

- Add `edit` + `update` to `BookingController` and routes under `bookings.view`
  with `permission:bookings.edit`.
- Reuse the store validation (shared `BookingRequest` or equivalent). On update:
  - **Availability re-check excludes the current booking** (so saving the same
    date is not a false self-conflict). Use `AvailabilityService` with an
    `ignoreBookingId`.
  - Recompute `total_amount` from package + venue + guest_count.
  - `PaymentService::recalculateBooking` remains the sole writer of
    `paid_amount` / `balance_amount`; call it after saving totals.
  - Disallow editing terminal bookings (`completed` / `cancelled`) → flash error.
- **Vue:** `Bookings/Edit` reusing the Create form component, pre-filled.
  Add an "Edit" action on `Bookings/Show` (hidden for terminal statuses).

### Tests
Update happy path; availability self-conflict excluded; conflict with a *different*
booking rejected; totals recomputed; balance recalculated; terminal-status edit
blocked; permission + tenant isolation.

## SP-4 — Unified events calendar

- Route `GET /admin/calendar` (`permission:bookings.view`),
  `CalendarController@index`.
- Returns the page plus bookings as FullCalendar event objects: `id`, `title`
  (client + event_type), `start` (event_date), `url`/click → `bookings.show`,
  `className`/color keyed by status. Optional month range query param; default to
  a wide window (all upcoming + recent).
- **Vue `Calendar/Index`** using `@fullcalendar/vue3` (already installed):
  dayGrid month + timeGrid week + list views, status legend, click → booking.
- Sidebar nav link ("Calendar") in `AppLayout`, gated by `bookings.view`.

### Tests
Calendar page renders for a permitted user; events payload contains the tenant's
bookings and excludes other tenants'; unauthorized role blocked.

## Non-goals

- No global omnibox search (deferred).
- No new design tokens / colors — reuse the editorial-pro ui component library.
- No queue worker requirement (sync by default).
- No SMS / push channels (email + in-app database only).

## Verification (every sub-project)

- `php artisan test` — full suite stays green; new feature tests added.
- `npm run build` — clean.
- `php artisan migrate:fresh --seed` — clean; ShowcaseSeeder data populates the
  new pages/calendar.
- One commit per sub-project.
