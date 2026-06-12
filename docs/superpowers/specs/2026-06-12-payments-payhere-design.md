# Payments + PayHere Integration — Design Spec

**Date:** 2026-06-12
**Sub-project:** 1 of 4 in the launch-readiness roadmap (Payments → Email/Notifications → Reliability → Security hardening).
**Status:** Approved design, ready for implementation plan.

## Goal

Let tenants (white-label event companies) take real money. Two capabilities:

1. **Record manual/offline payments** (cash, bank transfer, cheque, card) — currently impossible; `PaymentController` is read-only.
2. **Online payment via PayHere** — client portal "Pay now" for booking deposit/balance, with a server-to-server webhook as the single source of truth.

Booking `paid_amount` / `balance_amount` stay correct after every payment event.

## Context (current state, verified 2026-06-12)

- `payments` table: `payment_number`, `booking_id`, `client_id`, `installment_name`, `amount`, `payment_method` (string), `payment_date`, `reference_number`, `status` enum (`pending|completed|failed|refunded`), `notes`, `received_by`. No gateway fields.
- `bookings`: `total_amount`, `paid_amount` (default 0), `balance_amount`.
- `Admin\PaymentController` has `index` only (list + summary). No create/store/show/receipt.
- `Portal\PortalController::payments()` renders a read-only list.
- `Tenant` model has `getSetting($key,$default)` / `setSetting($key,$value)` over a JSON `settings` column (`array` cast).
- PDF export already used (dompdf) for quotations/reports — reuse the pattern for receipts.
- `MAIL_MAILER=log`, `QUEUE_CONNECTION=sync` (email + queue are later sub-projects; this spec does not depend on them — receipts are downloadable PDFs, not emailed).
- Full suite green (194 passed) before this work.

## Design decisions

1. **Approach A — hosted redirect checkout + webhook.** Browser auto-submits a signed form to PayHere's hosted checkout. No JS SDK. Onsite popup (`payhere.js`) is a possible later enhancement built on the same webhook.
2. **Per-tenant PayHere credentials.** Each tenant collects to their own PayHere merchant account. Creds live in `tenant.settings['payhere']`. Merchant secret stored **encrypted** (Laravel `Crypt`). Platform sandbox creds in `config/services.php` as demo fallback.
3. **Webhook is authoritative.** The JS/return-url path never marks a payment paid. Only the verified `notify_url` POST mutates payment/booking state.

## Data model

Migration `add_gateway_columns_to_payments_table`:

| Column | Type | Purpose |
|---|---|---|
| `gateway` | string(30) nullable | `payhere`, or null for manual |
| `gateway_payment_id` | string(64) nullable, unique | PayHere `payment_id`; reconcile + idempotency |
| `gateway_status_code` | smallInteger nullable | raw PayHere `status_code` |
| `currency` | char(3) default `LKR` | |
| `gateway_response` | json nullable | full webhook payload (audit) |

- `status` enum unchanged.
- `order_id` sent to PayHere = the Payment row `id`. A `pending` Payment is created **before** redirect so the webhook can look it up by `id`.
- `payment_method` reuses the existing string column: `payhere`, `cash`, `bank_transfer`, `cheque`, `card`.
- `gateway_payment_id` unique index gives idempotency at the DB layer.

## Components

### `App\Services\PayHereService`
- `credentialsFor(Tenant $tenant): array` — returns `merchant_id`, `merchant_secret` (decrypted), `sandbox` bool, `currency`, resolving tenant settings then `config('services.payhere')` fallback. Throws/returns null-marker when unconfigured.
- `buildCheckout(Payment $payment): array` — checkout URL + form fields including `hash`.
  `hash = strtoupper(md5(merchant_id . order_id . amount_2dp . currency . strtoupper(md5(merchant_secret))))`.
  `amount_2dp` = amount formatted to 2 decimals, no thousands separator.
- `verifyNotification(array $payload): bool` — recompute `md5sig` and compare (constant-time):
  `md5sig = strtoupper(md5(merchant_id . order_id . payhere_amount . payhere_currency . status_code . strtoupper(md5(merchant_secret))))`.
- `mapStatusCode(int $code): string` — `2 → completed`, `0 → pending`, `-1 → failed` (canceled), `-2 → failed`, `-3 → refunded`.

### `App\Services\PaymentService`
- `recordManual(Booking $booking, array $data, User $receivedBy): Payment` — creates a `completed` payment, generates `payment_number`, recalculates booking.
- `applyGatewayResult(Payment $payment, array $payload): void` — sets status from `mapStatusCode`, stores `gateway_payment_id`, `gateway_status_code`, `gateway_response`, recalculates booking when `completed`/`refunded`.
- `recalculateBooking(Booking $booking): void` — `paid = sum(amount where status=completed)`, `balance = total_amount - paid`. Single writer of booking balances.
- `generatePaymentNumber(Tenant $tenant): string` — unique per `(tenant_id, payment_number)`.

### `config/services.php`
Add `payhere` block: `merchant_id`, `merchant_secret`, `sandbox` (bool), `currency` (default `LKR`), `checkout_url` resolved from sandbox flag (`https://sandbox.payhere.lk/pay/checkout` vs `https://www.payhere.lk/pay/checkout`). Sourced from env for the platform demo account.

### `App\Http\Controllers\Admin\PaymentController` (extend)
- `create(Booking)` / `store(Booking)` — record manual payment (validated). Permission `payments.create`.
- `show(Payment)` — detail.
- `receipt(Payment)` — streamed dompdf receipt (`pdf/receipt.blade.php`).

### `App\Http\Controllers\Portal\PaymentController` (new)
- `initiate(Request)` — validates the booking belongs to the authenticated client and amount ≤ outstanding balance; creates pending Payment; renders an auto-submitting checkout form view. Role `client`.
- `return(Request)` — cosmetic "we're confirming your payment" page (does not mutate state).
- `cancel(Request)` — marks the still-`pending` Payment `failed` (only if not yet completed by webhook) and shows a canceled page.

### `App\Http\Controllers\PayHereWebhookController` (new, public)
- `notify(Request)` — the `notify_url` handler. Public route, CSRF-exempt, no auth, tenant-agnostic.

### `resources/views/pdf/receipt.blade.php`
Receipt: tenant branding, payment number, booking ref, amount, method, date, status. Mirrors `pdf/quotation.blade.php`.

## Routes

```
// web.php — admin group (permission-gated)
GET  /admin/bookings/{booking}/payments/create  payments.create
POST /admin/bookings/{booking}/payments         payments.store
GET  /admin/payments/{payment}                  payments.show
GET  /admin/payments/{payment}/receipt          payments.receipt

// portal group (role:client)
POST /portal/payments/initiate                  portal.payments.initiate
GET  /portal/payments/return                     portal.payments.return
GET  /portal/payments/cancel                     portal.payments.cancel
GET  /portal/payments/{payment}/receipt          portal.payments.receipt

// public, CSRF-exempt, no tenant middleware
POST /payhere/notify                             payhere.notify
```

`/payhere/notify` added to CSRF exceptions in `bootstrap/app.php` (`$middleware->validateCsrfTokens(except: ['payhere/notify'])`). It must NOT carry `SetCurrentTenant` / `tenant.active` / `auth` middleware.

## Online payment flow

1. Client clicks **Pay now** in the portal (deposit or full balance) → `POST /portal/payments/initiate`.
2. Server validates ownership + amount, creates Payment `pending` (`gateway=payhere`), calls `PayHereService::buildCheckout`, renders a page whose form auto-submits to the tenant's PayHere checkout URL (`return_url`, `cancel_url`, `notify_url` are absolute app URLs).
3. Client pays on PayHere, returns to `return`/`cancel` (cosmetic only).
4. PayHere POSTs to `/payhere/notify` (authority).

## Webhook security (critical path — must pass all, in order)

1. Look up Payment by `order_id` using `withoutGlobalScope('tenant')`. Not found → 200 ignore (don't leak).
2. Derive tenant from the Payment; load that tenant's PayHere secret.
3. Recompute `md5sig`; constant-time compare to posted value. Mismatch → 400, log, no state change.
4. Verify posted `merchant_id` == tenant's `merchant_id`, and `payhere_amount` / `payhere_currency` == Payment `amount` / `currency`. Mismatch → 400, log, no state change.
5. Idempotency: if Payment already `completed`, return 200 no-op.
6. `PaymentService::applyGatewayResult` (sets status, stores `gateway_payment_id` + raw payload, recalculates booking).

Secret handling: stored encrypted via `Crypt` in `tenant.settings['payhere']['merchant_secret']`; decrypted only inside `PayHereService`. Never serialized to the frontend, never logged. Settings UI is write-only for the secret (shows "configured", not the value).

## UI

- **Tenant Settings** → PayHere section: merchant id, merchant secret (write-only), sandbox toggle, currency. Gated `tenant_owner|admin`. Saves via `SettingsController`, secret encrypted before persist.
- **Admin `Payments/Index`** (exists) → add **Record payment** action, per-row receipt download, `gateway` + `status` badges. Existing filters/summary retained.
- **Admin Booking show** → payments panel (total/paid/balance) + **Add payment**.
- **Portal `Payments.vue`** → outstanding balance + installments, **Pay now** (hidden when tenant has no PayHere creds), receipt download, return/cancel result pages.

## Error handling

| Case | Behavior |
|---|---|
| Tenant has no PayHere creds | Portal Pay now hidden; admin settings shows notice |
| Bad `md5sig` | 400, logged, no mutation |
| `merchant_id` / amount / currency mismatch | 400, logged, no mutation |
| status_code canceled/failed (−1/−2) | Payment `failed`; booking untouched |
| Duplicate/replayed webhook | Idempotent 200 no-op |
| Manual record amount > balance | Validation error unless explicit overpay override flag |
| Refund (−3) | Payment `refunded`; booking recalculated |

## Testing (all sqlite-safe, no GD/image deps)

**Unit**
- `buildCheckout` hash matches a known vector.
- `verifyNotification` accepts a correctly-signed payload, rejects a tampered one.
- `mapStatusCode` covers 2/0/−1/−2/−3.
- `credentialsFor` resolves tenant settings, then config fallback.
- Merchant secret encryption round-trips; ciphertext != plaintext.

**Feature**
- `initiate` creates a pending Payment with the correct checkout payload + ownership enforced.
- Webhook success → Payment `completed` + booking `paid_amount`/`balance_amount` updated.
- Webhook bad signature → 400, no state change.
- Webhook amount mismatch → 400, no state change.
- Cross-tenant: a webhook signed with tenant B's secret for a tenant A Payment is rejected.
- Idempotent replay → second POST is a no-op, balances unchanged.
- Canceled status → Payment `failed`, booking untouched.
- Manual record (admin) → Payment `completed`, booking balance updated; overpay blocked without override.
- Receipt PDF renders (HTTP 200, `application/pdf`).
- RBAC: client can pay only own booking; only staff (`payments.create`) record manual; client cannot hit admin payment routes.

## Out of scope (later sub-projects)

- Emailing receipts / payment confirmations (sub-project 2).
- Queue-backed webhook processing / retries (sub-project 3 — webhook stays synchronous here, it's fast and idempotent).
- Onsite `payhere.js` popup checkout (possible enhancement on this foundation).
- Refund initiation via PayHere API (only inbound refund status handled here).
