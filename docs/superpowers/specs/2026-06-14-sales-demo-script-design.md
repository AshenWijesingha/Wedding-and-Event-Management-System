# Sales-Demo Script + Seed Polish — Design Spec

**Date:** 2026-06-14
**Sub-project:** 5 of 5 (final) in the redesign + demo roadmap
(1 design system ✓ → 2 onboarding wizard ✓ → 3 guided tours ✓ → 4 public demo sandbox ✓ → **5 sales-demo script**).
**Status:** Implemented on branch `feat/sales-demo-script` (curated provisioner + runbook; suite 238 green). Note: inquiry status enum is `pending/contacted/qualified/proposal_sent/negotiating/converted/closed` — hero inquiry uses `proposal_sent`.

## Goal

Give a salesperson a repeatable, polished demo: a scene-by-scene runbook that drives the public "Try the demo" sandbox, backed by a curated, deterministic dataset so every demo tells the same clean story (a wedding from inquiry → quotation → confirmed booking → deposit paid → balance due).

Two parts: (A) curate `DemoTenantProvisioner` to seed a fixed "hero story" instead of random factory data; (B) write the sales runbook.

## Context (verified 2026-06-14)

- Public sandbox: landing "Try the demo" → `POST /demo/start` (gated by `DEMO_MODE`) → `DemoTenantProvisioner::provision()` creates an isolated tenant + `tenant_owner` + seeded data, logs in, lands on `/admin`. Demos expire in 60 min, reaped by `demo:reap`.
- `DemoTenantProvisioner::seed()` currently uses random factories (3 venues, 4 packages, 6 clients, 8 bookings, 5 payments, etc.), each passed an explicit `tenant_id`.
- `DemoSandboxTest` asserts `3` venues and `8` bookings for a provisioned tenant — these assertions must stay aligned with the curated counts.
- Onboarding is marked dismissed in the demo tenant settings; guided tours auto-run (good for the demo).
- Currency is LKR; the existing `grand-vista` `DemoDataSeeder` already uses Sri Lankan venue names (reuse that flavour). The `grand-vista` seeder itself is **not** modified.
- Model fields available: Booking (venue/client/package ids, event_type, event_date, guest_count, total/paid/balance, status, booking_number), Quotation (client/venue/package/inquiry/booking ids, status, totals, quotation_number, valid_until), Payment (booking/client ids, amount, payment_method, status, payment_date, installment_name, payment_number), Inquiry (client/venue/package ids), plus Venue/Package/Client/Vendor/Staff/Task.

## Part A — Curated seed (`DemoTenantProvisioner`)

Replace the random `seed(int $tid)` body with a deterministic builder. All records hand-curated (no faker) for visible entities; explicit `tenant_id` and explicit foreign keys throughout. Helper methods keep it readable (`seedVenues`, `seedPackages`, `seedClients`, `seedHeroStory`, `seedSupporting`).

**Venues (3):** Mahaweli Grand Ballroom (100–500, base 1,500,000), Cinnamon Garden Lawns (50–250, base 850,000), Galle Face Terrace (30–150, base 650,000). LKR.

**Packages (4):** Silver (base 450,000), Gold (850,000), Platinum (1,500,000), Bespoke (2,500,000) — each with min/max guests and a short inclusion list.

**Clients (6):** named couples/organisers, including the hero client **Priya Fernando & Arjun Mendis** (`first_name` "Priya & Arjun", `last_name` "Fernando-Mendis", email `priya.arjun@example.lk`). Five more named clients for variety.

**Hero story** (the thread the runbook walks):
- Inquiry: hero client, wedding, ~250 guests, Mahaweli Grand Ballroom, Platinum package, status `quoted`/`contacted`.
- Quotation: hero client + venue + Platinum package, status `accepted`, `total_amount` 1,850,000, sensible subtotal/discount/tax, `valid_until` ~30 days out.
- Booking: hero client + venue + Platinum, `event_type` wedding, `event_date` ~90 days out, `guest_count` 250, `total_amount` 1,850,000, `paid_amount` 500,000, `balance_amount` 1,350,000, `status` confirmed.
- Payment: that booking, `installment_name` "Deposit", `amount` 500,000, `payment_method` bank_transfer, `status` completed, dated ~2 weeks ago. (Booking paid/balance set to match so the single writer stays consistent.)

**Supporting cast** for a populated-but-clean app:
- ~5 more bookings across statuses (pending, tentative, confirmed, completed) for the other clients, each with a venue+package, realistic totals; a couple with completed payments.
- 2 more quotations (one sent, one draft), 2 more inquiries (new/contacted).
- 4 vendors (photographer, caterer, florist, music — named), 3 staff (coordinator/manager/assistant), a handful of tasks tied to the hero booking and staff.

Counts will be deterministic; update `DemoSandboxTest` to match (or assert `>=` thresholds + the specific hero records rather than exact `3`/`8`). Keep the whole thing inside the existing `provision()` transaction; provisioning stays fast (~dozens of rows).

## Part B — Sales runbook (`docs/sales/demo-script.md`)

Markdown, structured for live use:

1. **Before you start** — ensure `DEMO_MODE=true`, the landing URL, note that each demo is a fresh 60-minute sandbox that resets itself (so you can't break it).
2. **Scenes** — each with **Click**, **Say** (talking points/value), and a **~time** budget:
   1. Landing → **Try the demo** — instant, zero signup; "you're inside a real workspace in one click."
   2. **Dashboard** + auto guided tour — fast to learn; point out the "?" replay.
   3. **Bookings → the Fernando-Mendis wedding** — the whole event in one place (event, venue, vendors, payment history).
   4. **Quotations → the accepted quote** + download PDF — branded, professional quotes in seconds.
   5. **Payments** — deposit paid (LKR 500k) and balance due (LKR 1.35M); show the client portal **Pay now / PayHere** — take real money online.
   6. **Settings → Branding** — change colour/logo live; white-label, it's *their* brand.
   7. **New-signup angle** — mention the onboarding wizard: a new company is live in minutes.
3. **Objection handling / FAQ** — pricing, data security/isolation, data migration, manual/offline payments, multi-currency.
4. **One-glance recap** — a table: Scene → Click → Say.

The runbook references only features that exist; no roadmap promises.

## Testing

- `DemoSandboxTest` updated: provision is still transactional + isolated; assert the **hero records** — a `confirmed` booking for the hero client with `paid_amount` 500,000 and `balance_amount` 1,350,000, and a `completed` deposit `Payment` of 500,000 for it. Adjust the venue/booking count assertions to the curated numbers (or `>=`).
- Full suite stays green; `npm run build` unaffected (no JS changes).
- Manual: with `DEMO_MODE=true`, "Try the demo" lands on a populated dashboard; the hero booking/quotation/payment read consistently.

## Out of scope

Video walk-through; modifying the fixed `grand-vista` `DemoDataSeeder`; CRM/lead capture from demo sessions; a printable one-pager (runbook includes the recap table instead).
