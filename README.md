# EventPro - Universal Event Management System

**A White-Label, Multi-Tenant Event Management Platform**

*Customizable for Hotels, Resorts, Banquet Halls, and Event Venues*

---

## Table of Contents

1. [Product Overview](#product-overview)
2. [Key Features](#key-features)
3. [Technology Stack](#technology-stack)
4. [Multi-Tenancy Design](#multi-tenancy-design)
5. [Customization Framework](#customization-framework)
6. [Development Setup](#development-setup)
7. [Database Design](#database-design)
8. [Core Modules](#core-modules)
9. [White-Label Configuration](#white-label-configuration)
10. [Theme Engine](#theme-engine)
11. [Plugin System](#plugin-system)
12. [API Documentation](#api-documentation)
13. [Development Timeline](#development-timeline)
14. [Testing Strategy](#testing-strategy)
15. [Deployment Guide](#deployment-guide)

---

## Product Overview

### Vision

**EventPro** is a comprehensive, white-label event management platform designed to serve any hospitality business that manages weddings, corporate events, conferences, and social functions. Built with customization at its core, each client can tailor the platform to their specific brand, workflow, and business requirements.

### Target Market

| Segment | Examples |
|---------|----------|
| Luxury Hotels | 5-star hotels with multiple event venues |
| Boutique Hotels | Small to medium hotels with banquet facilities |
| Resort Chains | Beach resorts, hill country resorts |
| Convention Centers | Large-scale conference and exhibition centers |
| Banquet Halls | Standalone event venues |
| Restaurant Groups | Restaurants with private dining/event spaces |
| Wedding Venues | Dedicated wedding destinations |
| Country Clubs | Golf clubs, sports clubs with event facilities |

### Product Editions

| Edition | Target | Features |
|---------|--------|----------|
| **Starter** | Small venues (1-2 halls) | Core booking, basic reporting |
| **Professional** | Medium venues (3-5 halls) | Full features, 5 users |
| **Business** | Large venues (5-10 halls) | Multi-user, API access |
| **Enterprise** | Hotel chains, franchises | Multi-property, white-label, custom integrations |

### Core Value Propositions

- **Rapid Deployment** - Go live in days, not months
- **Complete Customization** - Adapt to any workflow
- **Brand Consistency** - Full white-label capability
- **Scalability** - From single venue to hotel chains
- **Integration Ready** - Connect with existing systems
- **Self-Service** - Clients manage their own configuration

---

## Key Features

### Platform Modules

| Module | Description |
|--------|-------------|
| **Venue Management** | Multi-venue support with individual configurations |
| **Package Builder** | Dynamic package creation with modular services |
| **Booking Engine** | Complete booking lifecycle management |
| **Payment Tracking** | Multi-installment payments with reminders |
| **Quotation Generator** | Automated quotes with PDF generation |
| **Vendor Coordination** | External vendor management and assignments |
| **Menu Management** | Catering and menu customization |
| **Staff Scheduler** | Event staffing and task management |
| **Reporting Module** | Analytics and business intelligence |
| **Client Portal** | Self-service portal for customers |
| **Notification Center** | Email and SMS automation |

### Customization Features

| Feature | Description |
|---------|-------------|
| **Custom Event Types** | Define any event category |
| **Dynamic Pricing** | Flexible rules for seasons, days, guest counts |
| **Multi-Currency** | Support for any currency |
| **Multi-Language** | Full i18n support |
| **Custom Fields** | Add fields to any entity |
| **Workflow Builder** | Configurable approval flows |
| **Document Templates** | Customizable PDF templates |
| **Role Builder** | Granular permission control |
| **Theme Engine** | Visual customization |
| **Plugin System** | Extend functionality |

---

## Technology Stack

### Backend

| Component | Technology |
|-----------|------------|
| Language | PHP 8.2+ |
| Framework | Laravel 11 |
| Database | MySQL 8.0 / MariaDB 10.6+ |
| Cache | Redis |
| Search | Laravel Scout + Meilisearch |
| Queue | Redis / Database |

### Frontend

| Component | Technology |
|-----------|------------|
| Admin SPA | Vue.js 3 + Inertia.js |
| Public Site | Blade + Alpine.js |
| CSS | Tailwind CSS 3 |
| Components | Headless UI |
| Calendar | FullCalendar 6 |
| Charts | Chart.js / ApexCharts |

### Key Packages

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "inertiajs/inertia-laravel": "^1.0",
        "spatie/laravel-multitenancy": "^3.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-settings": "^3.0",
        "spatie/laravel-translatable": "^6.0",
        "spatie/laravel-activitylog": "^4.7",
        "spatie/laravel-medialibrary": "^11.0",
        "barryvdh/laravel-dompdf": "^2.0",
        "maatwebsite/excel": "^3.1",
        "intervention/image": "^3.0"
    }
}
```

---

## Multi-Tenancy Design

### Tenancy Modes

EventPro supports three tenancy modes:

#### Mode 1: Single Database (Column-Based)
Best for SaaS deployments with many small tenants.

```
┌─────────────────────────────────────────┐
│            SHARED DATABASE              │
├─────────────────────────────────────────┤
│  venues (tenant_id, ...)                │
│  bookings (tenant_id, ...)              │
│  packages (tenant_id, ...)              │
└─────────────────────────────────────────┘
```

#### Mode 2: Database Per Tenant
Best for enterprise clients requiring data isolation.

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   tenant_1   │  │   tenant_2   │  │   tenant_3   │
│   database   │  │   database   │  │   database   │
└──────────────┘  └──────────────┘  └──────────────┘
```

#### Mode 3: Single-Tenant (Standalone)
Best for on-premise installations.

### Tenant Model

```php
// app/Models/Tenant.php
<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'plan',
        'status',
        'trial_ends_at',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    public function hasFeature(string $feature): bool
    {
        return $this->getPlan()->hasFeature($feature);
    }
}
```

### Tenant-Aware Trait

```php
// app/Models/Concerns/BelongsToTenant.php
<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Auto-scope queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = app('currentTenant')) {
                $builder->where('tenant_id', $tenant->id);
            }
        });

        // Auto-assign tenant on create
        static::creating(function (Model $model) {
            if ($tenant = app('currentTenant')) {
                $model->tenant_id = $tenant->id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

---

## Customization Framework

### Configuration Hierarchy

```
Level 4: User Preferences (per user overrides)
    ↓
Level 3: Tenant Settings (client-specific)
    ↓
Level 2: Environment Config (.env)
    ↓
Level 1: Application Defaults (config files)
```

### Settings Service

```php
// app/Services/SettingsService.php
<?php

namespace App\Services;

use App\Models\Tenant;

class SettingsService
{
    private array $defaults;
    private ?Tenant $tenant;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? app('currentTenant');
        $this->defaults = config('eventpro.defaults');
    }

    public function get(string $key, $default = null)
    {
        // Check tenant settings first
        if ($this->tenant) {
            $tenantValue = $this->tenant->getSetting($key);
            if ($tenantValue !== null) {
                return $tenantValue;
            }
        }

        // Fall back to application defaults
        return data_get($this->defaults, $key, $default);
    }

    public function set(string $key, $value): void
    {
        if ($this->tenant) {
            $this->tenant->setSetting($key, $value);
        }
    }
}
```

### Configurable Settings Categories

```php
// config/eventpro/settings-schema.php
return [
    'general' => [
        'business_name' => ['type' => 'text', 'required' => true],
        'tagline' => ['type' => 'text'],
        'timezone' => ['type' => 'select', 'options' => 'timezones', 'default' => 'UTC'],
        'date_format' => ['type' => 'select', 'default' => 'd M Y'],
        'time_format' => ['type' => 'select', 'default' => 'h:i A'],
    ],

    'currency' => [
        'code' => ['type' => 'select', 'default' => 'USD'],
        'symbol' => ['type' => 'text', 'default' => '$'],
        'position' => ['type' => 'select', 'default' => 'before'],
        'decimal_places' => ['type' => 'number', 'default' => 2],
    ],

    'booking' => [
        'min_advance_days' => ['type' => 'number', 'default' => 7],
        'max_advance_days' => ['type' => 'number', 'default' => 365],
        'tentative_hold_hours' => ['type' => 'number', 'default' => 48],
        'require_deposit' => ['type' => 'boolean', 'default' => true],
        'deposit_percentage' => ['type' => 'number', 'default' => 25],
    ],

    'quotation' => [
        'validity_days' => ['type' => 'number', 'default' => 14],
        'auto_followup_days' => ['type' => 'array', 'default' => [3, 7, 14]],
        'number_prefix' => ['type' => 'text', 'default' => 'QT'],
    ],

    'payment' => [
        'installment_schedule' => [
            'type' => 'json',
            'default' => [
                ['name' => 'Advance', 'percentage' => 25],
                ['name' => 'Second', 'percentage' => 35],
                ['name' => 'Final', 'percentage' => 40],
            ],
        ],
        'accepted_methods' => [
            'type' => 'multiselect',
            'default' => ['bank_transfer', 'credit_card', 'cash'],
        ],
    ],

    'cancellation' => [
        'policy' => [
            'type' => 'json',
            'default' => [
                ['days_before' => 60, 'refund_percentage' => 50],
                ['days_before' => 30, 'refund_percentage' => 25],
                ['days_before' => 0, 'refund_percentage' => 0],
            ],
        ],
    ],

    'event_types' => [
        'enabled_types' => [
            'type' => 'multiselect',
            'default' => ['wedding', 'corporate', 'conference', 'birthday', 'other'],
        ],
        'custom_types' => ['type' => 'array', 'default' => []],
    ],
];
```

### Custom Fields System

```php
// app/Services/CustomFieldService.php
<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Model;

class CustomFieldService
{
    public function getFieldsFor(string $entityType)
    {
        return CustomField::where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getValues(Model $entity): array
    {
        $values = CustomFieldValue::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->get()
            ->keyBy('custom_field_id');

        $fields = $this->getFieldsFor($entity->getTable());
        $result = [];

        foreach ($fields as $field) {
            $value = $values->get($field->id);
            $result[$field->slug] = [
                'field' => $field,
                'value' => $value ? $value->value : $field->default_value,
            ];
        }

        return $result;
    }

    public function saveValues(Model $entity, array $values): void
    {
        foreach ($values as $fieldSlug => $value) {
            $field = CustomField::where('slug', $fieldSlug)->first();
            if (!$field) continue;

            CustomFieldValue::updateOrCreate(
                [
                    'custom_field_id' => $field->id,
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                ],
                ['value' => $value]
            );
        }
    }
}
```

---

## Development Setup

### Prerequisites

- PHP 8.2+ with required extensions
- Composer 2.5+
- Node.js 18+
- MySQL 8.0 or MariaDB 10.6+
- Redis 6.0+
- Git 2.40+

### Docker Setup (Recommended)

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: eventpro
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

  meilisearch:
    image: getmeili/meilisearch:latest
    ports:
      - "7700:7700"

volumes:
  mysql_data:
```

### Installation Steps

```bash
# 1. Clone repository
git clone https://github.com/[username]/eventpro.git
cd eventpro

# 2. Start Docker containers
docker-compose up -d

# 3. Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# 4. Environment setup
cp .env.example .env
docker-compose exec app php artisan key:generate

# 5. Database setup
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed

# 6. Build assets
docker-compose exec app npm run dev

# 7. Access application
# App: http://localhost:8000
# Mail: http://localhost:8025
```

### Manual Installation

```bash
# 1. Clone and install
git clone https://github.com/[username]/eventpro.git
cd eventpro
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your database credentials

# 3. Database setup
php artisan migrate
php artisan db:seed

# 4. Build and serve
npm run dev
php artisan serve
```

---

## Database Design

### Core Tables

#### Tenants
```sql
CREATE TABLE tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(255) UNIQUE NULL,
    plan_id BIGINT UNSIGNED NULL,
    logo VARCHAR(255) NULL,
    primary_color VARCHAR(7) DEFAULT '#3B82F6',
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    settings JSON NULL,
    status ENUM('active', 'suspended', 'trial') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### Venues
```sql
CREATE TABLE venues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    capacity_min INT UNSIGNED NOT NULL,
    capacity_max INT UNSIGNED NOT NULL,
    base_price DECIMAL(14, 2) NOT NULL,
    weekend_surcharge DECIMAL(12, 2) DEFAULT 0,
    amenities JSON NULL,
    images JSON NULL,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE INDEX (tenant_id, slug)
);
```

#### Packages
```sql
CREATE TABLE packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    base_price DECIMAL(14, 2) NOT NULL,
    min_guests INT UNSIGNED NOT NULL,
    max_guests INT UNSIGNED NOT NULL,
    guest_pricing JSON NULL,
    included_services JSON NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

#### Bookings
```sql
CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    booking_number VARCHAR(50) NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    setup_time TIME NOT NULL,
    event_start_time TIME NOT NULL,
    event_end_time TIME NOT NULL,
    guest_count INT UNSIGNED NOT NULL,
    total_amount DECIMAL(14, 2) NOT NULL,
    paid_amount DECIMAL(14, 2) DEFAULT 0,
    balance_amount DECIMAL(14, 2) NOT NULL,
    status ENUM('pending', 'tentative', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    custom_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    UNIQUE INDEX (tenant_id, booking_number)
);
```

#### Custom Fields
```sql
CREATE TABLE custom_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    options JSON NULL,
    validation_rules JSON NULL,
    default_value VARCHAR(255) NULL,
    is_required BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE INDEX (tenant_id, slug)
);

CREATE TABLE custom_field_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_field_id BIGINT UNSIGNED NOT NULL,
    entity_type VARCHAR(255) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    value TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (custom_field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
    UNIQUE INDEX (custom_field_id, entity_type, entity_id)
);
```

---

## Core Modules

### Availability Service

```php
// app/Services/AvailabilityService.php
<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    public function isVenueAvailable(int $venueId, string $date): bool
    {
        return !Booking::where('venue_id', $venueId)
            ->where('event_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    public function getAvailabilityCalendar(int $venueId, string $startDate, string $endDate): array
    {
        $bookings = Booking::where('venue_id', $venueId)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get(['event_date', 'status']);

        $calendar = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $booking = $bookings->firstWhere('event_date', $dateStr);
            
            $calendar[] = [
                'date' => $dateStr,
                'status' => $booking ? $this->mapStatus($booking->status) : 'available',
            ];
            
            $current->addDay();
        }

        return $calendar;
    }

    public function reserveVenue(int $venueId, string $date): bool
    {
        return DB::transaction(function () use ($venueId, $date) {
            $exists = Booking::where('venue_id', $venueId)
                ->where('event_date', $date)
                ->whereNotIn('status', ['cancelled'])
                ->lockForUpdate()
                ->exists();

            return !$exists;
        });
    }

    private function mapStatus(string $status): string
    {
        return match($status) {
            'pending', 'tentative' => 'tentative',
            'confirmed' => 'booked',
            default => 'available',
        };
    }
}
```

### Pricing Service

```php
// app/Services/PricingService.php
<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Venue;
use Carbon\Carbon;

class PricingService
{
    private SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function calculateEventPrice(array $params): array
    {
        $breakdown = [
            'venue' => 0,
            'package' => 0,
            'services' => [],
            'surcharges' => [],
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
        ];

        // Venue pricing
        if (!empty($params['venue_id'])) {
            $venue = Venue::findOrFail($params['venue_id']);
            $breakdown['venue'] = $this->calculateVenuePrice($venue, $params);
        }

        // Package pricing
        if (!empty($params['package_id'])) {
            $package = Package::findOrFail($params['package_id']);
            $breakdown['package'] = $this->calculatePackagePrice($package, $params['guest_count']);
        }

        // Services
        if (!empty($params['services'])) {
            foreach ($params['services'] as $service) {
                $breakdown['services'][] = $this->calculateServicePrice($service, $params['guest_count']);
            }
        }

        // Surcharges
        $breakdown['surcharges'] = $this->calculateSurcharges($params, $breakdown);

        // Calculate totals
        $breakdown['subtotal'] = $breakdown['venue'] 
            + $breakdown['package'] 
            + array_sum(array_column($breakdown['services'], 'total'))
            + array_sum(array_column($breakdown['surcharges'], 'amount'));

        // Apply discount
        if (!empty($params['discount'])) {
            $breakdown['discount'] = $this->calculateDiscount($breakdown['subtotal'], $params['discount']);
        }

        // Tax
        $taxRate = $this->settings->get('pricing.tax_rate', 0);
        $taxableAmount = $breakdown['subtotal'] - $breakdown['discount'];
        $breakdown['tax'] = $taxableAmount * ($taxRate / 100);

        // Total
        $breakdown['total'] = $taxableAmount + $breakdown['tax'];

        return $breakdown;
    }

    private function calculateVenuePrice(Venue $venue, array $params): float
    {
        $price = $venue->base_price;
        $eventDate = Carbon::parse($params['event_date']);

        if ($eventDate->isWeekend()) {
            $price += $venue->weekend_surcharge;
        }

        return $price;
    }

    private function calculatePackagePrice(Package $package, int $guestCount): float
    {
        $price = $package->base_price;

        // Tiered pricing
        if ($package->guest_pricing) {
            foreach ($package->guest_pricing as $tier) {
                if ($guestCount >= $tier['from'] && $guestCount <= $tier['to']) {
                    $price += ($guestCount - $package->min_guests) * $tier['price_per_guest'];
                    break;
                }
            }
        }

        return $price;
    }

    private function calculateSurcharges(array $params, array $breakdown): array
    {
        $surcharges = [];
        $eventDate = Carbon::parse($params['event_date']);

        // Peak season
        if ($this->isPeakSeason($eventDate)) {
            $rate = $this->settings->get('pricing.peak_season_surcharge', 15);
            $surcharges[] = [
                'name' => 'Peak Season Surcharge',
                'rate' => $rate,
                'amount' => ($breakdown['venue'] + $breakdown['package']) * ($rate / 100),
            ];
        }

        return $surcharges;
    }

    private function isPeakSeason(Carbon $date): bool
    {
        $peakMonths = $this->settings->get('pricing.peak_months', [12, 1, 2]);
        return in_array($date->month, $peakMonths);
    }

    private function calculateDiscount(float $subtotal, array $discount): float
    {
        if ($discount['type'] === 'percentage') {
            return $subtotal * ($discount['value'] / 100);
        }
        return $discount['value'];
    }
}
```

### Quotation Service

```php
// app/Services/QuotationService.php
<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotationService
{
    private PricingService $pricingService;
    private BrandingService $brandingService;

    public function __construct(PricingService $pricingService, BrandingService $brandingService)
    {
        $this->pricingService = $pricingService;
        $this->brandingService = $brandingService;
    }

    public function generateFromInquiry(Inquiry $inquiry, array $services = []): Quotation
    {
        $pricing = $this->pricingService->calculateEventPrice([
            'venue_id' => $inquiry->venue_id,
            'package_id' => $inquiry->package_id,
            'guest_count' => $inquiry->guest_count,
            'event_date' => $inquiry->event_date,
            'services' => $services,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => $this->generateNumber(),
            'inquiry_id' => $inquiry->id,
            'subtotal' => $pricing['subtotal'],
            'tax_amount' => $pricing['tax'],
            'total_amount' => $pricing['total'],
            'valid_until' => now()->addDays(14),
            'status' => 'draft',
            'prepared_by' => auth()->id(),
        ]);

        return $quotation;
    }

    public function generatePdf(Quotation $quotation): string
    {
        $quotation->load(['inquiry.client', 'inquiry.venue', 'items']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'branding' => $this->brandingService->getBranding(),
        ]);

        $filename = "quotation_{$quotation->quotation_number}.pdf";
        $path = "quotations/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function generateNumber(): string
    {
        $prefix = app(SettingsService::class)->get('quotation.number_prefix', 'QT');
        $year = date('Y');
        $sequence = Quotation::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s%s%04d', $prefix, $year, $sequence);
    }
}
```

---

## White-Label Configuration

### Branding Service

```php
// app/Services/BrandingService.php
<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    private ?Tenant $tenant;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? app('currentTenant');
    }

    public function getBranding(): array
    {
        if (!$this->tenant) {
            return $this->getDefaultBranding();
        }

        return [
            'business_name' => $this->tenant->name,
            'tagline' => $this->tenant->getSetting('general.tagline'),
            'logo' => $this->getLogoUrl(),
            'favicon' => $this->getFaviconUrl(),
            'colors' => [
                'primary' => $this->tenant->primary_color ?? '#3B82F6',
                'secondary' => $this->tenant->getSetting('branding.secondary_color', '#1E40AF'),
            ],
            'contact' => [
                'email' => $this->tenant->email,
                'phone' => $this->tenant->phone,
                'address' => $this->tenant->getSetting('contact.address'),
            ],
            'social' => [
                'facebook' => $this->tenant->getSetting('social.facebook'),
                'instagram' => $this->tenant->getSetting('social.instagram'),
            ],
        ];
    }

    public function getCssVariables(): string
    {
        $branding = $this->getBranding();

        return <<<CSS
:root {
    --color-primary: {$branding['colors']['primary']};
    --color-secondary: {$branding['colors']['secondary']};
}
CSS;
    }

    private function getLogoUrl(): string
    {
        $logo = $this->tenant?->logo;
        if ($logo && Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }
        return asset('images/logo.png');
    }

    private function getFaviconUrl(): string
    {
        $favicon = $this->tenant?->favicon;
        if ($favicon && Storage::disk('public')->exists($favicon)) {
            return Storage::disk('public')->url($favicon);
        }
        return asset('favicon.ico');
    }

    private function getDefaultBranding(): array
    {
        return [
            'business_name' => config('app.name'),
            'logo' => asset('images/logo.png'),
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#1E40AF',
            ],
        ];
    }
}
```

---

## Theme Engine

### Theme Structure

```
themes/
├── default/
│   ├── theme.json
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── views/
│       ├── layouts/
│       └── pages/
├── elegant/
│   └── ...
└── modern/
    └── ...
```

### Theme Configuration

```json
// themes/elegant/theme.json
{
    "name": "Elegant",
    "version": "1.0.0",
    "description": "A sophisticated theme for luxury venues",
    "colors": {
        "primary": "#8B4513",
        "secondary": "#D4AF37"
    },
    "fonts": {
        "heading": "Playfair Display",
        "body": "Lato"
    },
    "features": {
        "hero_slider": true,
        "parallax_sections": true
    }
}
```

### Theme Service

```php
// app/Services/ThemeService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ThemeService
{
    private string $activeTheme = 'default';

    public function __construct()
    {
        $this->loadActiveTheme();
    }

    private function loadActiveTheme(): void
    {
        $tenant = app('currentTenant');
        if ($tenant) {
            $this->activeTheme = $tenant->getSetting('theme.active', 'default');
        }
        $this->registerThemeViews();
    }

    public function getAvailableThemes(): array
    {
        $themesPath = resource_path('themes');
        $themes = [];

        foreach (File::directories($themesPath) as $themePath) {
            $configPath = "{$themePath}/theme.json";
            if (File::exists($configPath)) {
                $themes[basename($themePath)] = json_decode(File::get($configPath), true);
            }
        }

        return $themes;
    }

    private function registerThemeViews(): void
    {
        $themePath = resource_path("themes/{$this->activeTheme}/views");
        if (File::isDirectory($themePath)) {
            View::prependLocation($themePath);
        }
    }

    public function asset(string $path): string
    {
        return asset("themes/{$this->activeTheme}/assets/{$path}");
    }
}
```

---

## Plugin System

### Plugin Structure

```
plugins/
├── sms-gateway/
│   ├── plugin.json
│   ├── src/
│   │   ├── SmsGatewayServiceProvider.php
│   │   └── Services/
│   └── routes/
└── payment-gateway/
    └── ...
```

### Plugin Configuration

```json
// plugins/sms-gateway/plugin.json
{
    "name": "SMS Gateway",
    "slug": "sms-gateway",
    "version": "1.0.0",
    "description": "Send SMS notifications",
    "providers": [
        "Plugins\\SmsGateway\\SmsGatewayServiceProvider"
    ],
    "hooks": {
        "booking.created": "sendBookingConfirmationSms",
        "payment.received": "sendPaymentConfirmationSms"
    },
    "settings": {
        "gateway_provider": {
            "type": "select",
            "options": ["twilio", "nexmo"]
        },
        "api_key": {
            "type": "password"
        }
    }
}
```

### Plugin Service

```php
// app/Services/PluginService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PluginService
{
    private array $loadedPlugins = [];
    private array $hooks = [];

    public function loadPlugins(): void
    {
        $tenant = app('currentTenant');
        $enabledPlugins = $tenant?->getSetting('plugins.enabled', []) ?? [];

        foreach ($enabledPlugins as $pluginSlug) {
            $this->loadPlugin($pluginSlug);
        }
    }

    public function loadPlugin(string $slug): bool
    {
        $configPath = base_path("plugins/{$slug}/plugin.json");
        if (!File::exists($configPath)) {
            return false;
        }

        $config = json_decode(File::get($configPath), true);

        // Register providers
        foreach ($config['providers'] ?? [] as $provider) {
            app()->register($provider);
        }

        // Register hooks
        foreach ($config['hooks'] ?? [] as $hook => $handler) {
            $this->hooks[$hook][] = ['plugin' => $slug, 'handler' => $handler];
        }

        $this->loadedPlugins[$slug] = $config;
        return true;
    }

    public function executeHook(string $hook, array $data = []): array
    {
        $results = [];
        foreach ($this->hooks[$hook] ?? [] as $handler) {
            // Execute handler
        }
        return $results;
    }

    public function getAvailablePlugins(): array
    {
        $plugins = [];
        foreach (File::directories(base_path('plugins')) as $pluginPath) {
            $configPath = "{$pluginPath}/plugin.json";
            if (File::exists($configPath)) {
                $plugins[] = json_decode(File::get($configPath), true);
            }
        }
        return $plugins;
    }
}
```

---

## API Documentation

### Endpoints Overview

#### Public Endpoints
```
GET    /api/v1/venues                    List venues
GET    /api/v1/venues/{id}               Venue details
GET    /api/v1/venues/{id}/availability  Availability calendar
GET    /api/v1/packages                  List packages
POST   /api/v1/inquiries                 Submit inquiry
POST   /api/v1/calculate-price           Calculate pricing
```

#### Authenticated Endpoints
```
GET    /api/v1/client/bookings           Client's bookings
GET    /api/v1/client/bookings/{id}      Booking details

# Admin
GET    /api/v1/admin/bookings            All bookings
POST   /api/v1/admin/bookings            Create booking
PUT    /api/v1/admin/bookings/{id}       Update booking
DELETE /api/v1/admin/bookings/{id}       Cancel booking

GET    /api/v1/admin/reports/revenue     Revenue report
GET    /api/v1/admin/reports/bookings    Bookings report
```

### Response Format

```json
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "booking_number": "BK2026001",
        "status": "confirmed",
        "event": {
            "type": "wedding",
            "date": "2026-03-15",
            "guest_count": 150
        },
        "financial": {
            "total": 450000,
            "paid": 112500,
            "balance": 337500
        }
    }
}
```

---

## Development Timeline

### Phase 1: Foundation (Weeks 1-6)
- Project setup, multi-tenancy, authentication
- Database design, migrations, seeders
- Core architecture (models, services, repositories)

### Phase 2: Core Modules (Weeks 7-16)
- Venue management & availability
- Package system & dynamic pricing
- Inquiry & quotation workflow
- Booking engine & contracts
- Payment tracking

### Phase 3: Extended Modules (Weeks 17-24)
- Vendor & resource management
- Staff scheduling & tasks
- Reporting & analytics
- Client portal

### Phase 4: Customization (Weeks 25-30)
- Settings framework & custom fields
- Theme engine & branding
- Plugin system

### Phase 5: Launch (Weeks 31-36)
- Testing (unit, integration, UAT)
- Documentation
- Production deployment

---

## Testing Strategy

### Test Categories

```bash
# Run all tests
php artisan test

# Run specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Example Test

```php
// tests/Feature/BookingTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_double_booking()
    {
        $tenant = Tenant::factory()->create();
        $venue = Venue::factory()->for($tenant)->create();
        
        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'event_date' => '2026-03-15',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/admin/bookings', [
                'venue_id' => $venue->id,
                'event_date' => '2026-03-15',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_date']);
    }
}
```

---

## Deployment Guide

### Production Requirements

| Component | Requirement |
|-----------|-------------|
| PHP | 8.2+ |
| MySQL | 8.0+ |
| RAM | 4GB minimum |
| Storage | 50GB SSD |
| Web Server | Nginx 1.20+ |

### Deployment Checklist

```bash
# 1. Clone repository
git clone https://github.com/[username]/eventpro.git

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Configure environment
cp .env.example .env
# Edit .env with production values
php artisan key:generate

# 4. Database
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder

# 5. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage

# 7. Storage link
php artisan storage:link

# 8. Queue worker (supervisor)
# 9. Cron job for scheduler
```

---

## Repository Name Suggestions

Given the universal product nature:

| Name | Rationale |
|------|-----------|
| `eventpro` ⭐ | Short, professional, brandable |
| `eventpro-platform` | Emphasizes platform nature |
| `venue-booking-system` | Descriptive |
| `hospitality-events` | Industry-focused |

**Recommended: `eventpro`**

---

## License

Proprietary software. See LICENSE.md for details.

---

## Support

For documentation, visit: [docs.eventpro.io](https://docs.eventpro.io)

---

*EventPro - Professional Event Management for Modern Hospitality*

*Version: 1.0.0*
