# Hotel / Venue / Package Approval Workflow — Design

**Date:** 2026-07-02
**Status:** Approved (design), pending implementation plan

## Context

Today a tenant's staff create **venues** (halls) and **packages** through the admin
area and they go **live immediately** — visible on the public site and selectable in
the quotation/booking builders. There is no review step and no concept of a **hotel**
that groups its halls and offerings.

The business needs a submission-and-approval workflow: a hotel manager assembles a
hotel profile with its venues and packages, submits each for review, and a **platform
super admin** approves or rejects before anything becomes public or bookable. This
gives the platform operator editorial control over what tenants publish.

## Goals

- New **Hotel** entity grouping Venues and Packages, tenant-scoped.
- Per-item approval (**each Hotel, Venue, Package** carries its own approval state).
- Managers submit items; super admin approves/rejects with feedback.
- Only **approved** items appear on the public site and are selectable in
  quotations/bookings.
- Editing an approved item stays live but flags it for re-review.
- Extend the offline **dev:doctor / dev:baseline / dev:restore** recovery tool and every
  other touchpoint (seeders, onboarding, API, nav, factories) so the system stays
  self-consistent.

## Non-goals

- No content versioning / draft-vs-published copies (edits apply live).
- No new dedicated role — reuse existing tenant roles.
- No public-facing hotel booking pages beyond surfacing approved data in existing views.
- No revert-on-reject of already-live edits (reject records feedback; data stays live).

## Approach

Store approval state as **columns on each model** plus a reusable `Approvable` trait,
rather than a polymorphic `approvals` table. This matches the existing `status`
column pattern, keeps queries simple (`->approved()`), and review history is captured
by the existing activity log. A polymorphic table would add indirection for only three
models.

## 1. Data model

### New `Hotel` model (`app/Models/Hotel.php`)
`use BelongsToTenant, HasFactory, Approvable;`

| column | type | notes |
|---|---|---|
| tenant_id | FK | auto-filled by BelongsToTenant |
| name | string | |
| slug | string, unique per tenant | route key |
| city | string, nullable | |
| address | string, nullable | |
| description | text, nullable | |
| star_rating | tinyint, nullable | 1–5 |
| images | json, nullable | |
| status | string default `active` | manager live on/off toggle (orthogonal to approval) |
| + Approvable columns (below) | | |

Relations: `hasMany(Venue)`, `hasMany(Package)`.

### FKs on existing tables
- `venues.hotel_id` → hotels.id, **nullable** (nullable for safe migration; app requires it on create).
- `packages.hotel_id` → hotels.id, **nullable** (a package may be hotel-agnostic).

### `Approvable` trait + columns (on hotels, venues, packages)
`app/Models/Concerns/Approvable.php`

| column | type | default |
|---|---|---|
| approval_status | string | `draft` |
| submitted_at | timestamp null | |
| submitted_by | FK users null | |
| reviewed_at | timestamp null | |
| reviewed_by | FK users null | |
| review_notes | text null | rejection/feedback text |
| changes_pending_review | boolean | false |

Trait provides:
- `scopeApproved($q)` → `where('approval_status','approved')`
- `scopePendingReview($q)` → pending OR changes_pending_review (super-admin queue)
- `submit(User)`, `approve(User)`, `reject(User,$notes)` transition helpers
- `markChangesPending()` used by model `updating` observer when an approved record is edited
- accessors: `isApproved()`, `isPending()`, `isRejected()`

`approval_status` values: `draft | pending | approved | rejected`.

## 2. State machine

```
draft ──submit──▶ pending ──approve──▶ approved
  ▲                  │                    │
  │                  └──reject──▶ rejected│ (edit approved item)
  └──────(resubmit from rejected)         ▼
                                   approved + changes_pending_review=true
                                          │
                             approve ─────┴──▶ clears changes_pending_review
```

- **Create** → `draft`.
- **Submit** (`hotels.submit` etc.): `draft|rejected → pending`; validates required
  fields complete; sets `submitted_at/by`; notifies super admin.
- **Approve** (super admin): → `approved`; clears `changes_pending_review`; sets
  `reviewed_at/by`; notifies submitter.
- **Reject** (super admin): → `rejected` with `review_notes`; notifies submitter.
  For an approved item that was edited (changes_pending_review), reject records notes,
  clears the flag, and leaves the item `approved`/live (documented behaviour — no revert).
- **Edit of an approved item**: applies live; `updating` observer sets
  `changes_pending_review=true` (item stays `approved`, stays public/selectable).
  Brand-new (`draft`/`pending`) items remain hidden until first approval.

## 3. Gating (what "approved" unlocks)

`->approved()` scope gates:
- **Public site:** landing featured venues, `/venues`, `/venues/{slug}`, `/packages`
  (`App\Http\Controllers\VenueController`, `PackageController`, home route). A hotel’s
  venues only show if **both** the venue and its parent hotel are approved.
- **Selectors:** quotation builder (`Admin\QuotationController@create` data →
  `Quotations/Create.vue`) and booking creation only offer approved venues/packages.
- **API:** `Api/V1/Admin/VenueController`, `PackageController` public/list endpoints
  return approved for non-privileged consumers (managers still see their own).

Managers see **all** their items (any status) in the admin area with status badges.

## 4. Permissions (reuse roles)

Add to `RolePermissionSeeder`:
- Tenant perms: add `hotels.view`, `hotels.create`, `hotels.edit`, `hotels.delete`,
  and one **submit** permission per approvable type — `hotels.submit`, `venues.submit`,
  `packages.submit`. Granted to `tenant_owner` + `admin` (full, incl. all submits);
  `manager` (view/create/edit + all submits, no delete); `staff` (view only).
- Platform perms (super admin only): `approvals.view`, `approvals.review`.

Enforced via `permission:` route middleware and policies; cross-tenant isolation is
already guaranteed by `BelongsToTenant`.

## 5. UI

### Manager (tenant admin area)
- **Hotels index** (`resources/js/Pages/Hotels/Index.vue`): cards/table with approval
  status badges (Draft / Pending / Approved / Approved·changes pending / Rejected),
  "Submit for approval" action, rejection notes shown inline.
- **Hotel create/edit** (`Hotels/Create.vue`, `Edit.vue`): hotel fields + nested
  management of its Venues and Packages (link/create), each row showing its own
  status + submit control.
- Extend existing **Venue** and **Package** create/edit pages with a `hotel_id`
  selector, an approval-status badge, and a Submit button; rejected items show notes.
- New `Admin\HotelController` (resource) + extend `Admin\VenueController` /
  `Admin\PackageController` with `submit` actions.

### Super admin (platform area)
- **Approvals queue** (`resources/js/Pages/Admin/Approvals/Index.vue`, route
  `/admin/approvals`, `role:super_admin`): unified list of pending +
  changes-pending items across Hotel/Venue/Package, filterable by type, with tenant +
  submitter + submitted-at.
- **Review detail** (`Approvals/Show.vue`): full item preview + **Approve** /
  **Reject (with notes)** actions. New `Admin\ApprovalController`
  (`index`, `show`, `approve`, `reject`).
- Nav: add "Hotels" to the tenant admin sidebar and "Approvals" (with pending count
  badge) to the super-admin section of `AppLayout`.

## 6. Notifications

Reuse the existing `notifications` table / notification system:
- On **submit** → notify all super admins ("<Type> '<name>' submitted for approval").
- On **approve/reject** → notify the submitter (with review notes on reject).
Delivered in-app (existing bell/notification UI); no email required (offline-safe).

## 7. Data migration of existing content

A migration + seeder step:
- Group existing venues by the prefix before `" — "` in their name (e.g.
  "Cinnamon Grand Colombo") → create one `Hotel` per unique prefix per tenant, set
  `venues.hotel_id`.
- Mark all pre-existing hotels, venues, and packages `approval_status = approved`
  (they are already live) with `reviewed_at = now()`.
- Existing packages: leave `hotel_id` null (hotel-agnostic) unless trivially inferable.

## 8. Recovery tool & "update everywhere" touchpoints

The change must leave the whole system self-consistent. Update:

- **dev:baseline** — after implementation, run `php artisan dev:baseline --force` to
  regenerate `tools/demo/baseline/manifest.json` + `snapshot/` so the new source
  files (Hotel model, `Approvable` trait, HotelController, ApprovalController,
  migrations, Vue pages, seeders, factories) are part of the known-good baseline that
  `dev:doctor` verifies and `dev:restore` restores. No logic change to
  `DevDoctorCommand`/`DevBaselineCommand`/`DevRestoreCommand` is expected (they walk the
  tree), but confirm the new paths are captured and re-baseline is committed.
- **dev:doctor** functional suite auto-runs the new tests; confirm it stays green.
- **Seeders:** `DatabaseSeeder` runs a new `HotelSeeder` (or extends
  `DemoDataSeeder` / `ShowcaseSeeder`) to create hotels, link venues/packages, and set
  `approved` status; add a few pending/rejected samples so the approvals queue demos.
- **Onboarding:** `OnboardingController@storeVenue/@storePackage` set `hotel_id` +
  approval defaults (`draft`).
- **Factories:** `HotelFactory`; `VenueFactory` / `PackageFactory` set `hotel_id` and a
  sensible default `approval_status` (`approved` for existing tests to keep passing;
  add explicit states `->pending()/->rejected()`).
- **API controllers:** `Api/V1/Admin/VenueController` / `PackageController` apply the
  approved gating and accept `hotel_id`.
- **Nav / shell:** `AppLayout` sidebar (Hotels, Approvals).
- **Docs:** note the workflow in `docs/admin-guide.md` and `DEMO-README.md`.
- **RolePermissionSeeder:** new permissions above.

## 9. Testing

Feature tests (`tests/Feature/Admin/…`):
- **Submit transitions:** draft→pending sets fields + notifies; incomplete item is
  rejected by validation; rejected→pending on resubmit.
- **Approve/reject:** super admin approves → item public/selectable; reject sets notes
  + notifies; non-super-admin gets 403 on review routes.
- **Gating:** public venue/package queries and quotation selectors exclude
  non-approved; approved venue under a non-approved hotel stays hidden.
- **Edit-of-approved:** sets `changes_pending_review`, item stays public/selectable,
  appears in queue; approve clears the flag.
- **Permissions & isolation:** manager cannot approve; a tenant cannot see/act on
  another tenant's items (BelongsToTenant).
- **Migration:** existing venues get a hotel and `approved` status; counts intact.

## 10. Implementation order (for the plan)

1. Migrations (hotels table, approvable columns, FKs) + backfill migration.
2. `Approvable` trait, `Hotel` model, relations, factories, scopes, observer.
3. Permissions + policies + seeders.
4. Manager UI: HotelController + Venue/Package submit + Vue pages.
5. Super-admin Approvals queue + review + notifications.
6. Public/API/quotation gating.
7. Tests (alongside each slice, TDD).
8. Re-baseline (`dev:baseline --force`) + docs + `dev:doctor` green.
