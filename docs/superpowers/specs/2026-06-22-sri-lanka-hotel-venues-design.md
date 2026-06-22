# Real Sri Lankan hotel venues — industry-grade seed data

**Date:** 2026-06-22
**Status:** Approved

## Context

The app shipped with three generic placeholder venues ("Mahaweli Grand Ballroom",
etc.) seeded on the primary tenant, using external `picsum.photos` images and a
hardcoded `$` currency symbol on the public venue pages. For a Sri Lankan
wedding/event product this reads as a demo, not a real solution. The goal is to
make it look like a genuine industry-level platform by seeding **real leading Sri
Lankan hotels and their reception halls**, with realistic services, capacities,
LKR pricing, branded imagery, and matching service packages.

The primary/public tenant is **Mangala Events (Pvt) Ltd** (slug `grand-vista`),
already configured with `currency: LKR / Rs`. `Tenant::current()` resolves this
tenant on public routes (single-tenancy), so the public `/venues` page shows its
venues. `DemoDataSeeder` seeds this tenant; `ShowcaseSeeder` seeds a separate
showcase tenant. Both wire bookings/inquiries/quotations to venues positionally
(`$venues[$i % count]`), so swapping the venue dataset is safe with ≥3 venues.

## Decisions (from brainstorming)

- **Granularity:** one `Venue` per reception hall/ballroom (per-ballroom).
- **Existing data:** replace the demo venues entirely.
- **Polish:** rupee currency display, branded image banners, service packages,
  maximal real-world accuracy.

## Design

### 1. Single source of truth — `database/seeders/data/SriLankaHotels.php`

A plain (non-model) class with two static methods:

- `halls(): array` — venue definitions keyed for positional use. Each entry maps
  to the `Venue` fillable: `name` (e.g. `Cinnamon Grand Colombo — Oak Room`),
  `slug`, `description` (reception/setting info), `capacity_min`, `capacity_max`,
  `base_price` (LKR), `weekend_surcharge`, `amenities` (real services array),
  `images` (`['/images/venues/<hotel-slug>.svg']`), `status => active`.
- `packages(): array` — reception service-package definitions (see §4).

**Hotels (8) and reception halls (~3 each, ~24 venues):**

| Hotel (city) | Halls |
|---|---|
| Cinnamon Grand Colombo | Oak Room, King's Court, Grand Ballroom |
| Shangri-La Colombo | Grand Ballroom, Colombo Ballroom, Lotus Hall |
| Galle Face Hotel, Colombo | Regency Ballroom, The Verandah, 1864 Lawn |
| The Kingsbury Colombo | Grand Ballroom, Winchester, Sky Lounge |
| Hilton Colombo | Grand Ballroom, Oak Room, Bay Leaf Terrace |
| Heritance Kandalama, Dambulla | Kandalama Ballroom, Kachchan Hall, Lake Terrace |
| Jetwing Lighthouse, Galle | Sailfish Ballroom, Cinnamon Lawn, Clifftop Deck |
| Earl's Regency, Kandy | Regency Ballroom, Mahaweli Hall, Hill Country Lawn |

Hotel names, cities, and well-known hall names are real. **Capacities and prices
are indicative/representative figures, not official quotes** — noted in the class
docblock. Services reflect what these properties genuinely offer (in-house
catering, customizable menus, AV + stage, LED video walls, bridal suite, valet
parking, dedicated wedding coordinator, dance floor, sound & lighting, Wi-Fi).

### 2. Branded image banners — `public/images/venues/<hotel-slug>.svg`

One tasteful SVG banner per hotel (8 files): brand-colour gradient, hotel name,
city, and a subtle motif. Each hall references its hotel banner via `images`.
Replaces external `picsum.photos` URLs — offline, no hotlinking/copyright issue.
The public views already render `images[0]` as `<img src>` and gracefully handle
the no-image case.

### 3. Replace demo venues in both seeders

- `DemoDataSeeder` (primary/public Mangala tenant): replace the inline `$venues`
  array with `SriLankaHotels::halls()`. Downstream booking/inquiry/quotation
  wiring is unchanged (positional/modulo; ≥3 venues guaranteed). Prune legacy
  venue slugs (`mahaweli-grand-ballroom`, `pol-watta-garden-terrace`,
  `galle-face-rooftop-pavilion`) before reseeding for idempotency.
- `ShowcaseSeeder` (`seedVenues`): source the same dataset (≥10 halls) so the
  showcase tenant is equally real and `ShowcaseSeederTest` (≥10 of each entity)
  stays green.

### 4. Service packages

Replace the generic seeded packages with real Sri Lankan reception packages,
linked to halls via the existing `venue_packages` pivot:

- Poruwa Wedding — Silver / Gold / Platinum
- Nikah Reception
- Homecoming Reception
- Corporate Gala Dinner

Per-plate LKR pricing where natural; `base_price` in LKR. Keep within the
existing `Package` fillable schema.

### 5. Currency polish

Replace the hardcoded `$` with `Rs ` in `resources/views/venues/index.blade.php`
and `resources/views/venues/show.blade.php` (matches the tenant LKR setting).
Apply the same to the public packages index view if it prints a price.

## Out of scope

- No new DB columns/migrations — reuse `Venue`/`Package` as-is.
- No global currency-formatting refactor; only the public venue/package views.
- No real hotel photography (branded SVGs instead).

## Verification

1. `php artisan migrate:fresh --seed` (or via the launcher on a fresh DB).
2. `php artisan test` — full suite green; `ShowcaseSeederTest` still asserts ≥10.
3. Drive the launcher → public `/venues`: real hotels render with `Rs` pricing,
   branded banners, service chips; open a hall detail → services + packages show.
   Screenshot `/venues` and one hall detail as evidence.
