# In-App Guided Tours — Design Spec

**Date:** 2026-06-14
**Sub-project:** 3 of 5 in the redesign + demo roadmap
(1 design system ✓ → 2 onboarding wizard ✓ → **3 in-app demo tour** → 4 public demo sandbox → 5 sales-demo script).
**Status:** Approved design, ready for implementation.

## Goal

Help new users learn each admin screen in place. Every primary admin page gets its own contextual **guided tour** — a spotlight walkthrough of that page's key elements. A page's tour auto-runs the first time a user visits it and can be replayed anytime from a "?" button. Per-user completion is remembered so it never nags.

The rich demo data already exists (`DemoDataSeeder` seeds the `grand-vista` tenant with venues, packages, bookings, quotations, payments — login `admin@demo.eventpro.test` / `password`), so this sub-project is purely the tour layer.

## Context (verified 2026-06-14)

- No tour library installed.
- `User` has a `preferences` attribute in `$fillable` (JSON column). Confirm/extend the cast to `array` during implementation.
- Admin shell `resources/js/Layouts/AppLayout.vue` holds the sidebar nav (stable structure, good shared anchor target).
- Design-system component lib (`@/Components/ui`) + tokens available and required for any new UI.
- Inertia shares `auth.user` already (`HandleInertiaRequests`).

## Design decisions

1. **driver.js** for the spotlight + tooltips (~5kb, MIT, keyboard nav, highlight cutout). No Vue lock-in.
2. **One reusable component + central registry.** Tour copy/steps live in a single registry, not scattered through pages. Pages opt in with a one-line component + `data-tour` anchors.
3. **Auto-run once per page per user; replayable.** Completion stored in `User.preferences['completed_tours']`.

## Components

### `resources/js/tours/registry.js`
Exports `tours`, a map `tourKey → { steps: [...] }`. Each step:
```js
{ element: '[data-tour="bookings.new"]', popover: { title, description, side } }
```
All titles/descriptions live here. One entry per page (`dashboard`, `bookings`, `booking-show`, `booking-create`, `inquiries`, `quotations`, `quotation-show`, `clients`, `venues`, `packages`, `vendors`, `tasks`, `payments`, `reports`, `settings`).

### `resources/js/Components/PageTour.vue`
- Prop `tourKey: String`.
- On mount → `nextTick` → if `tourKey` not in `completedTours` (from shared Inertia state), build a `driver()` instance from `tours[tourKey].steps` and start it; on first completion/skip, `POST /tours/complete { key }` and locally mark done.
- Always renders a small fixed "?" replay button (editorial-pro `ui` styling). Clicking it runs the same tour regardless of completion.
- Reads `prefers-reduced-motion`; when set, disables driver.js animations.
- Guards: if `tours[tourKey]` missing or no anchored elements are present, render only the replay button (no crash). driver.js skips missing-element steps.

### `resources/js/Components/ui` usage
Replay button uses tokens (`bg-surface`, `text-ink-muted`, `border-border`, `text-primary` on hover). Driver popovers themed via a small CSS override in `app.css` to match tokens (primary buttons, ink text, surface background, rounded-lg).

### Anchoring
Highlighted elements get `data-tour="<key>.<step>"`. Shared chrome (sidebar nav links, topbar) gets `data-tour` anchors added once in `AppLayout.vue`, reused across page tours that reference navigation. Page-specific elements get anchors added in each page.

## Persistence

- `User.preferences['completed_tours']` = array of tour keys.
- Route `POST /tours/complete` (auth, any admin-area role) → `TourController@complete`, validates `key` (string, in the known registry keys list mirrored server-side as a small allow-list), appends to the user's `preferences['completed_tours']` if absent, saves. Idempotent.
- `HandleInertiaRequests::share` adds `completedTours => $request->user()?->preferences['completed_tours'] ?? []` (only when authenticated). `PageTour` reads `$page.props.completedTours`.

## Routes

```
POST /tours/complete   tours.complete   // auth; not tenant- or role-gated beyond auth
```
Placed in the authenticated web routes (outside the admin role group so any logged-in user could complete a tour key; admin-only pages are where the tours run, but the endpoint itself only writes the caller's own preferences).

## Flow

1. User lands on e.g. `/admin/bookings`. `<PageTour tour-key="bookings" />` mounts.
2. `bookings` not in `completedTours` → tour auto-runs, spotlighting the create button, filters, table, row actions.
3. User finishes/skips → `POST /tours/complete { key: 'bookings' }` → server appends; client marks done so it won't re-fire this session.
4. Later, user clicks the "?" replay button → tour runs again (completion unchanged).

## Error handling

| Case | Behavior |
|---|---|
| `tourKey` not in registry | Component renders replay button only; no tour, no crash |
| Anchored element missing | driver.js skips that step (defensive registry uses optional steps) |
| `POST /tours/complete` with unknown key | 422 validation (server allow-list) |
| `preferences` null | Treated as `[]`; first write initializes the array |
| Reduced-motion preference | Animations disabled; tour still functions |

## Testing

**Feature**
- `POST /tours/complete` appends the key to `user.preferences['completed_tours']`; second identical call is a no-op (no duplicate).
- Unknown/invalid key → 422.
- Unauthenticated → redirected to login.
- `HandleInertiaRequests` shares `completedTours` for an authenticated user (assert via an Inertia page prop).

**Build / manual**
- `npm run build` clean with driver.js + registry + `PageTour` wired into pages.
- Manual/headless screenshot: a page tour renders the spotlight + themed popover; replay button visible.

## Out of scope (later sub-projects)

Public demo sandbox + scheduled reset (sub-project 4), sales-demo script (5), multi-page resuming walkthroughs, portal-side tours, embedded video/media.
