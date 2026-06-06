# EventPro Developer Guide

## Architecture

**Stack:**
- PHP 8.2+ / Laravel 11
- Vue 3 + Inertia.js (admin SPA)
- Tailwind CSS 3
- SQLite (dev) / MySQL or PostgreSQL (production)

**Key packages:**
- `spatie/laravel-multitenancy` — column-based multi-tenancy
- `spatie/laravel-permission` — roles and permissions
- `barryvdh/laravel-dompdf` — PDF generation
- `inertiajs/inertia-laravel` — Inertia server-side adapter

---

## Directory Structure

```
app/
  Http/
    Controllers/
      Admin/          — Inertia web controllers (/admin/*)
      Api/V1/Admin/   — JSON API controllers (/api/v1/admin/*)
      Api/V1/Client/  — Client portal API controllers
      Portal/         — Client portal Inertia controllers
    Middleware/
      SecurityHeaders.php
      EnsureValidTenant.php
      HandleInertiaRequests.php
    Requests/
      StoreBookingRequest.php  — booking validation with double-booking check
  Models/
    Concerns/
      BelongsToTenant.php  — global scope trait for tenant isolation
  Services/
    PricingService.php    — event price calculation
    QuotationService.php  — quotation generation and PDF
    BrandingService.php   — tenant branding (logo, colors)
    SettingsService.php   — tenant settings access
    ThemeService.php      — public theme management
    PluginService.php     — plugin loading and hook execution
    CustomFieldService.php — dynamic field CRUD
    AvailabilityService.php — venue availability calendar

resources/
  js/
    Pages/        — Vue 3 Inertia page components
    Layouts/      — AppLayout, PortalLayout
  views/
    pdf/
      quotation.blade.php  — PDF template
  themes/
    default/      — Default theme (theme.json + views/)
    elegant/      — Elegant theme

plugins/
  sms-gateway/    — SMS plugin (plugin.json)
  payment-gateway/ — Payment plugin

database/
  migrations/
  factories/
  seeders/
    DemoDataSeeder.php  — Seeds demo venues, bookings, clients, staff
```

---

## Multi-Tenancy

Column-based tenancy via `BelongsToTenant` trait. Set `TENANCY_MODE=column` in `.env`.

```php
// Set current tenant context
$tenant->makeCurrent();

// All queries on BelongsToTenant models are automatically scoped
Venue::all(); // returns only this tenant's venues
```

Tenant settings are stored as JSON in `tenants.settings`:
```php
$tenant->setSetting('general.timezone', 'America/New_York');
$tenant->getSetting('general.timezone', 'UTC'); // with default
```

---

## Services

### PricingService

```php
$pricing = app(PricingService::class)->calculateEventPrice([
    'venue_id' => 1,
    'package_id' => 2,
    'guest_count' => 150,
    'event_date' => '2025-06-15',
    'services' => [...],
    'discount' => ['type' => 'percentage', 'value' => 10],
]);
// Returns: venue, package, services, surcharges, subtotal, discount, tax, total
```

### PluginService

```php
$pluginService = app(PluginService::class);
$pluginService->loadPlugins(); // loads all enabled plugins
$pluginService->executeHook('notification.sms', ['message' => 'Hello!']);
```

**Plugin structure** (`plugins/{slug}/plugin.json`):
```json
{
  "slug": "my-plugin",
  "name": "My Plugin",
  "version": "1.0.0",
  "author": "Developer",
  "description": "Plugin description",
  "providers": ["Plugins\\MyPlugin\\MyPluginServiceProvider"],
  "hooks": {
    "notification.sms": "sendSms"
  }
}
```

### ThemeService

```php
$themeService = app(ThemeService::class);
$themeService->setActiveTheme('elegant'); // returns bool
$themeService->getThemeConfig(); // active theme config array
```

---

## API Resources

All API responses use Resource classes in `app/Http/Resources/`:
- `VenueResource`, `PackageResource`, `ClientResource`, `BookingResource`
- `QuotationResource`, `PaymentResource`, `VendorResource`, `StaffResource`, `TaskResource`

Responses follow the format:
```json
{ "data": {...}, "message": "..." }
// or for collections:
{ "data": [...], "meta": { "current_page": 1, "total": 50 } }
```

The `ApiResponse` trait provides helpers:
```php
return $this->success($resource, 'Created successfully', 201);
return $this->error('Not found', 404);
```

---

## Adding a New Module

1. Create migration in `database/migrations/`
2. Create Eloquent model in `app/Models/` (use `BelongsToTenant` if tenant-scoped)
3. Create API Resource in `app/Http/Resources/`
4. Create API controller in `app/Http/Controllers/Api/V1/Admin/`
5. Create Inertia controller in `app/Http/Controllers/Admin/`
6. Create Vue pages in `resources/js/Pages/{Module}/`
7. Register routes in `routes/web.php` and `routes/api.php`
8. Add factory in `database/factories/`

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific suite
php artisan test tests/Feature/Admin/
php artisan test tests/Unit/

# Run with coverage
php artisan test --coverage
```

`.env.testing` uses SQLite in-memory:
```
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
TENANCY_MODE=column
```

---

## Security Considerations

- `SecurityHeaders` middleware sets CSP, X-Frame-Options, HSTS (production)
- Rate limiting: login (5/min), API (120/min auth, 30/min public), inquiry (3/min)
- `StoreBookingRequest` validates venue availability, guest capacity, future date
- CSRF protection on all web routes (Laravel default)
- API uses Sanctum Bearer tokens
