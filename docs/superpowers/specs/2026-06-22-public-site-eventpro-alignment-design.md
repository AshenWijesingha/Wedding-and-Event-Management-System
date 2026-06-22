# Public site & data alignment — EventPro brand, footer, content, real vendors

**Date:** 2026-06-22
**Status:** Approved

## Context

After seeding real Sri Lankan hotels, the public site and seed data are now
inconsistent: the footer/header read the tenant brand "Mangala Events (Pvt)
Ltd" while the links page hardcodes "EventPro"; the footer address is partial
and shows no socials; the landing copy is generic and doesn't reflect the real
partner hotels; the contact page has no contact details; and the admin Vendors
section is sparse (4 bare records on the primary tenant). This change makes the
public experience coherent and industry-grade, branded as **EventPro**, and
aligned with the live data.

`BrandingService` reads everything from the current tenant (resolved on public
routes via single-tenancy), so the tenant settings in `TenantSeeder` are the
source of truth for brand/contact/social.

## Decisions (from brainstorming)

- **Brand:** EventPro everywhere (rebrand the demo tenant; pages use the dynamic
  brand, no hardcoded names).
- **Landing:** add a live "Featured Venues" section pulled from the DB.
- **Polish:** full address + city/country, seeded social links, contact-details
  panel, footer service-areas column.
- **Plus:** seed real event vendors for the admin Vendors section.

## Design

### 1. Brand & tenant settings — `TenantSeeder`

Rebrand the demo tenant to **EventPro** (keep slug `grand-vista` — `DemoDataSeeder`
resolves the tenant by that slug). Update: `name` → "EventPro"; `email` →
`hello@eventpro.lk`; `settings.general.business_name` → "EventPro". Enrich
`settings.contact` (address `No. 45, Galle Road`, city `Colombo 03`, country
`Sri Lanka`) and add `settings.social` (`facebook`, `instagram`, `linkedin`).

### 2. Footer — `resources/views/layouts/app.blade.php`

- Contact column shows the **full address**: address line, then city, then
  country (from `$branding['contact']`).
- Render the **LinkedIn** social icon in addition to Facebook/Instagram (the
  block already hides when none are set; now seeded).
- New **"Where we work"** column listing Colombo · Kandy · Galle · Dambulla.

### 3. Landing — `routes/web.php` (`home`) + `welcome.blade.php`

`home` route passes featured venues + counts:

```php
$featuredVenues = Venue::active()->orderByDesc('capacity_max')->take(4)->get();
$hallCount  = Venue::active()->count();
$hotelCount = Venue::active()->get()
    ->map(fn ($v) => Str::before($v->name, ' — '))->unique()->count();
```

- New **Featured Venues** section: real banner (`images[0]`), hotel/hall name,
  capacity, `Rs` price, link to the venue detail. Degrades gracefully if empty.
- Copy refresh referencing partner hotels across Colombo, Kandy, Galle &
  Dambulla.
- Stats driven by the passed counts (hotels / halls / rating).

### 4. Contact — `contact.blade.php`

Two-column layout: existing inquiry form on one side, a **contact-details panel**
(email/phone/address from `$branding`, plus a "Cities we serve" list) on the
other. Reuses the existing form + Alpine validation unchanged.

### 5. Links — `links.blade.php`

Replace the hardcoded "EventPro" heading with `$branding['business_name']` so it
follows the brand dynamically.

### 6. Real vendors — `Database\Seeders\Data\EventVendors` + seeders

New shared data class `EventVendors::all()` returning ~12 realistic Sri Lankan
event vendors with full fields (`name`, `category`, `contact_name`, `email`,
`phone`, `website`, `description`, `base_rate` in LKR, `rate_type`, `services[]`,
`status`). Categories span caterer, photographer, videographer, florist, decor,
entertainment, transport, makeup, cake, sound & lighting.

- `DemoDataSeeder`: replace the 4 sparse vendors with `EventVendors::all()`
  (full data) on the primary tenant.
- `ShowcaseSeeder::seedVendors`: source the same dataset (≥10, factory fills
  any gaps) so `ShowcaseSeederTest` stays green.

Vendor names are fictional-but-plausible to avoid attributing fake contact
details to real businesses.

## Out of scope

- No schema/migration changes.
- No changes to the admin Vendors UI itself (only the seed data behind it).

## Verification

1. `php artisan migrate:fresh --seed`.
2. `php artisan test` — full suite green; `ShowcaseSeederTest` ≥10 of each.
3. Drive the app and screenshot: landing (Featured Venues + new copy/stats),
   footer (full address, social icons, service areas), contact (details panel),
   admin Vendors (populated list). Confirm brand reads "EventPro" everywhere.
