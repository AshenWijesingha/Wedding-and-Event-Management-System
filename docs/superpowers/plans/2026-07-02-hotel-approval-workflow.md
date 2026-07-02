# Hotel Approval Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Hotel entity grouping Venues and Packages, with a per-item submit-for-approval workflow reviewed by the platform super admin, gating public/selectable visibility on approval.

**Architecture:** Approval state lives in columns on each model (`hotels`, `venues`, `packages`) via a shared `Approvable` trait (scopes + transition helpers + an `updating` observer that flags edits to approved records). A new tenant-scoped `Hotel` model owns venues/packages. Managers use extended admin CRUD + a Submit action; the super admin uses a new Approvals queue. Public/API/quotation reads filter with `->approved()`.

**Tech Stack:** Laravel 11, Inertia + Vue 3, Spatie Permission, Pest/PHPUnit, SQLite (sqlite file for demo, `:memory:` for tests), Tailwind.

## Global Constraints

- Multitenancy: every tenant-owned model uses `App\Models\Concerns\BelongsToTenant`; never add `orWhereNull` to tenant scoping (IDOR risk). Create platform (null-tenant) rows only when no tenant is current.
- Offline-safe: no external CDN/font links; DB sqlite, queue sync, mail log. Do not add network dependencies.
- `approval_status` enum values, verbatim: `draft`, `pending`, `approved`, `rejected`.
- Existing tests must stay green: factories default `approval_status = 'approved'` so pre-existing venue/package tests keep passing.
- Tests run with `APP_ENV=testing`, `DB_DATABASE=:memory:` (never touch the demo sqlite file).
- After implementation, regenerate the recovery baseline with `php artisan dev:baseline --force` and confirm `php artisan dev:doctor` is HEALTHY.
- Commit after every task (frequent commits).

---

## File Structure

**Create:**
- `database/migrations/2026_07_02_000001_create_hotels_table.php`
- `database/migrations/2026_07_02_000002_add_approvable_columns.php`
- `database/migrations/2026_07_02_000003_add_hotel_id_to_venues_and_packages.php`
- `database/migrations/2026_07_02_000004_backfill_hotels_from_venues.php`
- `app/Models/Hotel.php`
- `app/Models/Concerns/Approvable.php`
- `app/Observers/ApprovableObserver.php`
- `app/Policies/HotelPolicy.php`
- `app/Http/Controllers/Admin/HotelController.php`
- `app/Http/Controllers/Admin/ApprovalController.php`
- `app/Http/Resources/HotelResource.php`
- `app/Notifications/ApprovalSubmitted.php`, `app/Notifications/ApprovalReviewed.php`
- `database/factories/HotelFactory.php`
- `resources/js/Pages/Hotels/Index.vue`, `Create.vue`, `Edit.vue`
- `resources/js/Pages/Admin/Approvals/Index.vue`, `Show.vue`
- Tests under `tests/Feature/Admin/` (per task)

**Modify:**
- `app/Models/Venue.php`, `app/Models/Package.php` (traits, hotel relation, fillable)
- `app/Http/Controllers/Admin/VenueController.php`, `PackageController.php` (hotel_id + submit)
- `app/Http/Controllers/VenueController.php`, `PackageController.php` (public gating)
- `app/Http/Controllers/Admin/QuotationController.php` (approved selectors)
- `app/Http/Controllers/Api/V1/Admin/VenueController.php`, `PackageController.php`
- `routes/web.php` (hotels resource, submit routes, approvals routes)
- `database/seeders/RolePermissionSeeder.php`, `DemoDataSeeder.php` / `ShowcaseSeeder.php`
- `app/Providers/AppServiceProvider.php` (observer + policy registration)
- `resources/js/Layouts/AppLayout.vue` (nav: Hotels, Approvals)
- `database/factories/VenueFactory.php`, `PackageFactory.php`
- `docs/admin-guide.md`, `DEMO-README.md`

---

## Task 1: Migrations — hotels table, approvable columns, hotel FKs

**Files:**
- Create: `database/migrations/2026_07_02_000001_create_hotels_table.php`
- Create: `database/migrations/2026_07_02_000002_add_approvable_columns.php`
- Create: `database/migrations/2026_07_02_000003_add_hotel_id_to_venues_and_packages.php`
- Test: `tests/Feature/Admin/HotelSchemaTest.php`

**Interfaces:**
- Produces: `hotels` table; `approval_status, submitted_at, submitted_by, reviewed_at, reviewed_by, review_notes, changes_pending_review` columns on `hotels`, `venues`, `packages`; `hotel_id` nullable FK on `venues`, `packages`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/HotelSchemaTest.php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('hotels table and approvable columns exist', function () {
    expect(Schema::hasTable('hotels'))->toBeTrue();
    foreach (['hotels', 'venues', 'packages'] as $t) {
        expect(Schema::hasColumns($t, [
            'approval_status', 'submitted_at', 'submitted_by',
            'reviewed_at', 'reviewed_by', 'review_notes', 'changes_pending_review',
        ]))->toBeTrue();
    }
    expect(Schema::hasColumn('venues', 'hotel_id'))->toBeTrue();
    expect(Schema::hasColumn('packages', 'hotel_id'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HotelSchemaTest`
Expected: FAIL (`hotels` table missing).

- [ ] **Step 3: Write the migrations**

```php
// 2026_07_02_000001_create_hotels_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
```

```php
// 2026_07_02_000002_add_approvable_columns.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['hotels', 'venues', 'packages'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('approval_status')->default('draft')->index();
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('review_notes')->nullable();
                $table->boolean('changes_pending_review')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropConstrainedForeignId('submitted_by');
                $table->dropConstrainedForeignId('reviewed_by');
                $table->dropColumn([
                    'approval_status', 'submitted_at', 'reviewed_at',
                    'review_notes', 'changes_pending_review',
                ]);
            });
        }
    }
};
```

```php
// 2026_07_02_000003_add_hotel_id_to_venues_and_packages.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['venues', 'packages'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('hotel_id')->nullable()->after('tenant_id')
                    ->constrained('hotels')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['venues', 'packages'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropConstrainedForeignId('hotel_id');
            });
        }
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=HotelSchemaTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_02_00000*.php tests/Feature/Admin/HotelSchemaTest.php
git commit -m "feat(hotels): schema for hotels + approvable columns + hotel FKs"
```

---

## Task 2: Approvable trait + observer

**Files:**
- Create: `app/Models/Concerns/Approvable.php`
- Create: `app/Observers/ApprovableObserver.php`
- Test: `tests/Unit/ApprovableTest.php`

**Interfaces:**
- Produces: trait `App\Models\Concerns\Approvable` with `scopeApproved`, `scopePendingReview`, `submit(User $u): void`, `approve(User $u): void`, `reject(User $u, string $notes): void`, `isApproved(): bool`, `isPending(): bool`, `isRejected(): bool`; observer that sets `changes_pending_review=true` when an `approved` record is updated.
- Consumes: nothing (Task 3+ apply the trait to models).

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/ApprovableTest.php
<?php

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('submit approve reject transitions and edit flags changes', function () {
    $u = User::factory()->create();
    $hotel = Hotel::factory()->draft()->create();

    $hotel->submit($u);
    expect($hotel->approval_status)->toBe('pending')
        ->and($hotel->submitted_by)->toBe($u->id)
        ->and($hotel->submitted_at)->not->toBeNull();

    $hotel->approve($u);
    expect($hotel->approval_status)->toBe('approved')
        ->and($hotel->changes_pending_review)->toBeFalse();

    // editing an approved record flags it for re-review but stays approved
    $hotel->update(['name' => 'Renamed Hotel']);
    expect($hotel->fresh()->approval_status)->toBe('approved')
        ->and($hotel->fresh()->changes_pending_review)->toBeTrue();

    $hotel->reject($u, 'Needs better photos');
    expect($hotel->approval_status)->toBe('rejected')
        ->and($hotel->review_notes)->toBe('Needs better photos');
});

test('approved scope filters', function () {
    Hotel::factory()->approved()->create();
    Hotel::factory()->draft()->create();
    expect(Hotel::approved()->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ApprovableTest`
Expected: FAIL (trait/`Hotel` missing; comes together with Task 3 factory states — if `Hotel` factory not present yet, this fails to resolve; that is expected and fixed by Task 3. Run again after Task 3.)

- [ ] **Step 3: Write the trait and observer**

```php
// app/Models/Concerns/Approvable.php
<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Observers\ApprovableObserver;
use Illuminate\Database\Eloquent\Builder;

trait Approvable
{
    public static function bootApprovable(): void
    {
        static::observe(ApprovableObserver::class);
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where($this->getTable().'.approval_status', 'approved');
    }

    public function scopePendingReview(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w
            ->where('approval_status', 'pending')
            ->orWhere('changes_pending_review', true));
    }

    public function submit(User $user): void
    {
        $this->forceFill([
            'approval_status' => 'pending',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'review_notes' => null,
        ])->saveQuietly();
    }

    public function approve(User $user): void
    {
        $this->forceFill([
            'approval_status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'changes_pending_review' => false,
        ])->saveQuietly();
    }

    public function reject(User $user, string $notes): void
    {
        $this->forceFill([
            'approval_status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => $notes,
            'changes_pending_review' => false,
        ])->saveQuietly();
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }
}
```

```php
// app/Observers/ApprovableObserver.php
<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ApprovableObserver
{
    /**
     * When an already-approved record is edited through normal update(),
     * flag it for re-review but keep it approved (stays live). Transition
     * helpers use saveQuietly(), so they do not trigger this.
     */
    public function updating(Model $model): void
    {
        if (
            $model->approval_status === 'approved'
            && ! $model->isDirty('changes_pending_review')
            && ! $model->isDirty('approval_status')
            && $model->isDirty()
        ) {
            $model->changes_pending_review = true;
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes (after Task 3 factory exists)**

Run: `php artisan test --filter=ApprovableTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Concerns/Approvable.php app/Observers/ApprovableObserver.php tests/Unit/ApprovableTest.php
git commit -m "feat(hotels): Approvable trait + edit-flags-review observer"
```

---

## Task 3: Hotel model, relations, factory; apply trait to Venue/Package

**Files:**
- Create: `app/Models/Hotel.php`
- Create: `database/factories/HotelFactory.php`
- Modify: `app/Models/Venue.php`, `app/Models/Package.php`
- Modify: `database/factories/VenueFactory.php`, `database/factories/PackageFactory.php`
- Test: covered by Task 2 tests re-run.

**Interfaces:**
- Consumes: `Approvable` trait (Task 2).
- Produces: `App\Models\Hotel` with `venues()`, `packages()`, `submitter()`, `reviewer()`; `HotelFactory` states `draft()`, `pending()`, `approved()`, `rejected()`; `Venue`/`Package` gain `hotel()` relation + `hotel_id` fillable + `Approvable`.

- [ ] **Step 1: Write the Hotel model**

```php
// app/Models/Hotel.php
<?php

namespace App\Models;

use App\Models\Concerns\Approvable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use Approvable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'city', 'address',
        'description', 'star_rating', 'images', 'status',
    ];

    protected $casts = [
        'images' => 'array',
        'star_rating' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'changes_pending_review' => 'boolean',
    ];

    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

- [ ] **Step 2: Write the HotelFactory**

```php
// database/factories/HotelFactory.php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HotelFactory extends Factory
{
    public function definition(): array
    {
        $name = ucwords($this->faker->unique()->company()).' Hotel';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'city' => $this->faker->city(),
            'address' => $this->faker->streetAddress(),
            'description' => $this->faker->paragraph(),
            'star_rating' => $this->faker->numberBetween(3, 5),
            'images' => [],
            'status' => 'active',
            'approval_status' => 'approved',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['approval_status' => 'draft']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['approval_status' => 'pending', 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['approval_status' => 'approved', 'reviewed_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['approval_status' => 'rejected', 'review_notes' => 'Please revise.']);
    }
}
```

- [ ] **Step 3: Apply trait + relation to Venue and Package**

In `app/Models/Venue.php`: add `use App\Models\Concerns\Approvable;`, add `Approvable` to the `use` list in the class, add `'hotel_id'` to `$fillable`, add casts for `submitted_at`/`reviewed_at`/`changes_pending_review`, and add:

```php
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
```

Repeat identically for `app/Models/Package.php`.

In `database/factories/VenueFactory.php` and `PackageFactory.php`, add to the returned `definition()` array:

```php
            'approval_status' => 'approved',
```

(Do NOT set `hotel_id` in the base definition — leave null; tests that need a hotel set it explicitly. Add the same `draft()/pending()/approved()/rejected()` state methods as in HotelFactory.)

- [ ] **Step 4: Run Task 2 tests**

Run: `php artisan test --filter="ApprovableTest|HotelSchemaTest"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Hotel.php database/factories/HotelFactory.php app/Models/Venue.php app/Models/Package.php database/factories/VenueFactory.php database/factories/PackageFactory.php
git commit -m "feat(hotels): Hotel model + factory; Approvable on Venue/Package"
```

---

## Task 4: Register observer & policy; permissions

**Files:**
- Create: `app/Policies/HotelPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `database/seeders/RolePermissionSeeder.php`
- Test: `tests/Feature/Admin/HotelPermissionTest.php`

**Interfaces:**
- Produces: permissions `hotels.view/create/edit/delete/submit`, `venues.submit`, `packages.submit`, `approvals.view`, `approvals.review`; `HotelPolicy` mapping ability→permission.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/HotelPermissionTest.php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(\Database\Seeders\RolePermissionSeeder::class));

test('new permissions exist and super admin has approvals.review', function () {
    foreach (['hotels.view','hotels.create','hotels.submit','venues.submit','packages.submit','approvals.review'] as $p) {
        expect(\Spatie\Permission\Models\Permission::where('name', $p)->exists())->toBeTrue();
    }
    $tenant = Tenant::factory()->create();
    $super = User::factory()->create(['tenant_id' => null]);
    $super->assignRole('super_admin');
    expect($super->can('approvals.review'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HotelPermissionTest`
Expected: FAIL (permission `hotels.view` missing).

- [ ] **Step 3: Add permissions and policy**

In `database/seeders/RolePermissionSeeder.php`, add to the `$permissions` array:

```php
            // Hotels
            'hotels.view', 'hotels.create', 'hotels.edit', 'hotels.delete', 'hotels.submit',
            'venues.submit', 'packages.submit',
```

Add to `$platformPermissions`:

```php
            'approvals.view', 'approvals.review',
```

The existing `$superAdmin->syncPermissions(Permission::all())` already grants the platform perms. `tenant_owner` and `admin` receive all tenant perms via their existing `whereIn('name', $permissions)` sync, so no further change is needed for them. For `manager`, add to its explicit list:

```php
            'hotels.view', 'hotels.create', 'hotels.edit', 'hotels.submit',
            'venues.submit', 'packages.submit',
```

For `staff`, add `'hotels.view'` to its list.

Create `app/Policies/HotelPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

class HotelPolicy
{
    public function viewAny(User $user): bool { return $user->can('hotels.view'); }
    public function view(User $user, Hotel $hotel): bool { return $user->can('hotels.view'); }
    public function create(User $user): bool { return $user->can('hotels.create'); }
    public function update(User $user, Hotel $hotel): bool { return $user->can('hotels.edit'); }
    public function delete(User $user, Hotel $hotel): bool { return $user->can('hotels.delete'); }
    public function submit(User $user, Hotel $hotel): bool { return $user->can('hotels.submit'); }
}
```

In `app/Providers/AppServiceProvider.php` `boot()`, register the policy (follow the existing policy-registration pattern in that file; if policies are auto-discovered, no code needed — verify by running the test). The `Approvable` trait self-registers its observer via `bootApprovable`, so no observer registration is required here.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=HotelPermissionTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Policies/HotelPolicy.php app/Providers/AppServiceProvider.php database/seeders/RolePermissionSeeder.php tests/Feature/Admin/HotelPermissionTest.php
git commit -m "feat(hotels): permissions + HotelPolicy"
```

---

## Task 5: Backfill migration — group existing venues into hotels

**Files:**
- Create: `database/migrations/2026_07_02_000004_backfill_hotels_from_venues.php`
- Test: `tests/Feature/Admin/BackfillHotelsTest.php`

**Interfaces:**
- Consumes: `hotels` table, `venues.hotel_id`, `Approvable`.
- Produces: one hotel per `"Prefix — Room"` name prefix per tenant; every existing venue linked and `approved`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/BackfillHotelsTest.php
<?php

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('backfill groups venues by name prefix into approved hotels', function () {
    $tenant = Tenant::factory()->create();
    Venue::factory()->for($tenant)->create(['name' => 'Grand Palace — Ballroom', 'approval_status' => 'approved', 'hotel_id' => null]);
    Venue::factory()->for($tenant)->create(['name' => 'Grand Palace — Terrace', 'approval_status' => 'approved', 'hotel_id' => null]);

    Artisan::call('migrate', ['--path' => 'database/migrations/2026_07_02_000004_backfill_hotels_from_venues.php', '--force' => true]);

    expect(Hotel::where('name', 'Grand Palace')->count())->toBe(1);
    expect(Venue::whereNull('hotel_id')->count())->toBe(0);
    expect(Hotel::where('name', 'Grand Palace')->first()->approval_status)->toBe('approved');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BackfillHotelsTest`
Expected: FAIL (migration file not found).

- [ ] **Step 3: Write the backfill migration**

```php
// 2026_07_02_000004_backfill_hotels_from_venues.php
<?php
use App\Models\Hotel;
use App\Models\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Group by tenant + name prefix before ' — ' (fallback: whole name).
        Venue::withoutGlobalScopes()->whereNull('hotel_id')->get()
            ->groupBy(fn ($v) => $v->tenant_id.'|'.trim(Str::before($v->name, ' — ')))
            ->each(function ($venues, $key) {
                [$tenantId, $prefix] = explode('|', $key, 2);
                $hotel = Hotel::withoutGlobalScopes()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $prefix],
                    [
                        'slug' => Str::slug($prefix).'-'.Str::random(4),
                        'status' => 'active',
                        'approval_status' => 'approved',
                        'reviewed_at' => now(),
                    ]
                );
                Venue::withoutGlobalScopes()->whereIn('id', $venues->pluck('id'))
                    ->update(['hotel_id' => $hotel->id, 'approval_status' => 'approved']);
            });

        // Any existing packages become approved (hotel_id stays null / agnostic).
        \App\Models\Package::withoutGlobalScopes()->whereNull('approval_status')
            ->orWhere('approval_status', 'draft')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Venue::withoutGlobalScopes()->update(['hotel_id' => null]);
        Hotel::withoutGlobalScopes()->delete();
    }
};
```

Note: `saveQuietly`/mass `update()` here bypasses the observer (mass update does not fire model events), so `changes_pending_review` stays false. Good.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BackfillHotelsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_02_000004_backfill_hotels_from_venues.php tests/Feature/Admin/BackfillHotelsTest.php
git commit -m "feat(hotels): backfill existing venues into approved hotels"
```

---

## Task 6: Manager — HotelController resource + submit; routes

**Files:**
- Create: `app/Http/Controllers/Admin/HotelController.php`
- Create: `app/Http/Resources/HotelResource.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/HotelCrudTest.php`

**Interfaces:**
- Consumes: `Hotel`, `HotelPolicy`, permissions.
- Produces: routes `admin.hotels.*` (resource) + `admin.hotels.submit` (POST `hotels/{hotel}/submit`); `HotelResource`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/HotelCrudTest.php
<?php

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->owner = User::factory()->for($this->tenant)->create();
    $this->owner->assignRole('tenant_owner');
});

test('owner creates a hotel as draft then submits it', function () {
    $this->actingAs($this->owner)
        ->post('/admin/hotels', ['name' => 'Ocean Pearl', 'city' => 'Galle'])
        ->assertRedirect();

    $hotel = Hotel::where('name', 'Ocean Pearl')->first();
    expect($hotel->approval_status)->toBe('draft');

    $this->actingAs($this->owner)
        ->post("/admin/hotels/{$hotel->id}/submit")
        ->assertRedirect();

    expect($hotel->fresh()->approval_status)->toBe('pending');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HotelCrudTest`
Expected: FAIL (route missing → 404/405).

- [ ] **Step 3: Write HotelResource, controller, routes**

```php
// app/Http/Resources/HotelResource.php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'address' => $this->address,
            'description' => $this->description,
            'star_rating' => $this->star_rating,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'changes_pending_review' => $this->changes_pending_review,
            'review_notes' => $this->review_notes,
            'venues_count' => $this->whenCounted('venues'),
            'packages_count' => $this->whenCounted('packages'),
        ];
    }
}
```

```php
// app/Http/Controllers/Admin/HotelController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Hotel::class, 'hotel');
    }

    public function index(Request $request): Response
    {
        $hotels = Hotel::query()
            ->withCount(['venues', 'packages'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('approval_status', $s))
            ->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Hotels/Index', [
            'hotels' => HotelResource::collection($hotels),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Hotels/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['approval_status'] = 'draft';
        $hotel = Hotel::create($data);

        return redirect()->route('admin.hotels.edit', $hotel)->with('success', 'Hotel created. Submit it for approval when ready.');
    }

    public function edit(Hotel $hotel): Response
    {
        $hotel->load(['venues', 'packages']);

        return Inertia::render('Hotels/Edit', [
            'hotel' => (new HotelResource($hotel))->resolve(),
            'venues' => $hotel->venues,
            'packages' => $hotel->packages,
        ]);
    }

    public function update(Request $request, Hotel $hotel): RedirectResponse
    {
        $hotel->update($this->validated($request));

        return back()->with('success', 'Hotel updated.');
    }

    public function destroy(Hotel $hotel): RedirectResponse
    {
        $hotel->delete();

        return redirect()->route('admin.hotels.index')->with('success', 'Hotel deleted.');
    }

    public function submit(Hotel $hotel): RedirectResponse
    {
        $this->authorize('submit', $hotel);

        $request = request();
        abort_unless($hotel->name && $hotel->city, 422, 'Complete the hotel details before submitting.');

        $hotel->submit($request->user());
        // Task 8 adds: notify super admins.

        return back()->with('success', 'Submitted for approval.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|in:active,inactive',
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Hotel::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
```

In `routes/web.php`, inside the tenant admin group (near the venues resource, under an appropriate permission group), add:

```php
    Route::middleware('permission:hotels.view')->group(function () {
        Route::resource('hotels', App\Http\Controllers\Admin\HotelController::class)
            ->names('admin.hotels')->except([]);
        Route::post('hotels/{hotel}/submit', [App\Http\Controllers\Admin\HotelController::class, 'submit'])
            ->name('admin.hotels.submit');
    });
```

(Match the exact `->names()`/prefix convention used by the existing `venues` resource in that file.)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=HotelCrudTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/HotelController.php app/Http/Resources/HotelResource.php routes/web.php tests/Feature/Admin/HotelCrudTest.php
git commit -m "feat(hotels): manager Hotel CRUD + submit-for-approval"
```

---

## Task 7: Extend Venue/Package controllers — hotel_id + submit

**Files:**
- Modify: `app/Http/Controllers/Admin/VenueController.php`, `PackageController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/VenueSubmitTest.php`

**Interfaces:**
- Produces: routes `admin.venues.submit` (POST `venues/{venue}/submit`), `admin.packages.submit`; `store`/`update` accept nullable `hotel_id` and set `approval_status='draft'` on create.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/VenueSubmitTest.php
<?php

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->owner = User::factory()->for($this->tenant)->create();
    $this->owner->assignRole('tenant_owner');
});

test('new venue is draft and can be submitted', function () {
    $hotel = Hotel::factory()->for($this->tenant)->approved()->create();

    $this->actingAs($this->owner)->post('/admin/venues', [
        'name' => 'Sky Hall', 'hotel_id' => $hotel->id,
        'capacity_min' => 50, 'capacity_max' => 300, 'base_price' => 1000,
    ])->assertRedirect();

    $venue = Venue::where('name', 'Sky Hall')->first();
    expect($venue->approval_status)->toBe('draft')->and($venue->hotel_id)->toBe($hotel->id);

    $this->actingAs($this->owner)->post("/admin/venues/{$venue->id}/submit")->assertRedirect();
    expect($venue->fresh()->approval_status)->toBe('pending');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=VenueSubmitTest`
Expected: FAIL (submit route missing; `hotel_id` not persisted; new venue is `approved` not `draft`).

- [ ] **Step 3: Modify controllers + routes**

In `Admin/VenueController@store` validation array add:

```php
            'hotel_id' => 'nullable|exists:hotels,id',
```

After building `$validated`, set:

```php
        $validated['approval_status'] = 'draft';
```

(Leave `status` defaulting to `active` as today — approval, not `status`, gates visibility.) In `@update` validation add the same `hotel_id` rule. Add a `submit` method:

```php
    public function submit(Venue $venue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $venue);
        abort_unless(auth()->user()->can('venues.submit'), 403);
        $venue->submit(request()->user());

        return back()->with('success', 'Venue submitted for approval.');
    }
```

Apply the identical pattern to `Admin/PackageController` (`hotel_id` rule, `approval_status='draft'` on store, `submit` method, `packages.submit` gate).

In `routes/web.php`, add within the existing venue/package permission groups:

```php
        Route::post('venues/{venue}/submit', [App\Http\Controllers\Admin\VenueController::class, 'submit'])->name('admin.venues.submit');
        Route::post('packages/{package}/submit', [App\Http\Controllers\Admin\PackageController::class, 'submit'])->name('admin.packages.submit');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=VenueSubmitTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/VenueController.php app/Http/Controllers/Admin/PackageController.php routes/web.php tests/Feature/Admin/VenueSubmitTest.php
git commit -m "feat(hotels): venue/package hotel_id + submit-for-approval"
```

---

## Task 8: Super admin — Approvals queue, review, notifications

**Files:**
- Create: `app/Http/Controllers/Admin/ApprovalController.php`
- Create: `app/Notifications/ApprovalSubmitted.php`, `app/Notifications/ApprovalReviewed.php`
- Modify: `routes/web.php`; `HotelController@submit`, `VenueController@submit`, `PackageController@submit` (fire notification)
- Test: `tests/Feature/Admin/ApprovalReviewTest.php`

**Interfaces:**
- Consumes: `Approvable` transitions, permissions `approvals.view/review`.
- Produces: routes `admin.approvals.index`, `admin.approvals.approve` (POST `admin/approvals/{type}/{id}/approve`), `admin.approvals.reject`; `type` ∈ `hotel|venue|package`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Admin/ApprovalReviewTest.php
<?php

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->tenant = Tenant::factory()->create();
    $this->super = User::factory()->create(['tenant_id' => null]);
    $this->super->assignRole('super_admin');
});

test('super admin approves a pending hotel', function () {
    $hotel = Hotel::factory()->for($this->tenant)->pending()->create();

    $this->actingAs($this->super)
        ->post("/admin/approvals/hotel/{$hotel->id}/approve")
        ->assertRedirect();

    expect($hotel->fresh()->approval_status)->toBe('approved');
});

test('super admin rejects with notes', function () {
    $hotel = Hotel::factory()->for($this->tenant)->pending()->create();

    $this->actingAs($this->super)
        ->post("/admin/approvals/hotel/{$hotel->id}/reject", ['notes' => 'Add photos'])
        ->assertRedirect();

    expect($hotel->fresh()->approval_status)->toBe('rejected')
        ->and($hotel->fresh()->review_notes)->toBe('Add photos');
});

test('non super admin cannot review', function () {
    $owner = User::factory()->for($this->tenant)->create();
    $owner->assignRole('tenant_owner');
    $hotel = Hotel::factory()->for($this->tenant)->pending()->create();

    $this->actingAs($owner)
        ->post("/admin/approvals/hotel/{$hotel->id}/approve")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ApprovalReviewTest`
Expected: FAIL (routes missing).

- [ ] **Step 3: Write controller, notifications, routes**

```php
// app/Http/Controllers/Admin/ApprovalController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\ApprovalReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    private const TYPES = ['hotel' => Hotel::class, 'venue' => Venue::class, 'package' => Package::class];

    public function index(): Response
    {
        $this->authorizePlatform('approvals.view');

        $items = collect(self::TYPES)->flatMap(function ($class, $type) {
            return $class::withoutGlobalScopes()->pendingReview()
                ->with('submitter')->get()
                ->map(fn ($m) => [
                    'type' => $type,
                    'id' => $m->id,
                    'name' => $m->name,
                    'tenant_id' => $m->tenant_id,
                    'approval_status' => $m->approval_status,
                    'changes_pending_review' => $m->changes_pending_review,
                    'submitted_by' => $m->submitter?->name,
                    'submitted_at' => $m->submitted_at,
                ]);
        })->sortByDesc('submitted_at')->values();

        return Inertia::render('Admin/Approvals/Index', ['items' => $items]);
    }

    public function approve(string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);
        $model->approve(request()->user());
        $this->notifySubmitter($model, 'approved', null);

        return back()->with('success', ucfirst($type).' approved.');
    }

    public function reject(Request $request, string $type, int $id): RedirectResponse
    {
        $notes = $request->validate(['notes' => 'required|string|max:2000'])['notes'];
        $model = $this->find($type, $id);
        $model->reject(request()->user(), $notes);
        $this->notifySubmitter($model, 'rejected', $notes);

        return back()->with('success', ucfirst($type).' rejected.');
    }

    private function find(string $type, int $id)
    {
        $this->authorizePlatform('approvals.review');
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::withoutGlobalScopes()->findOrFail($id);
    }

    private function authorizePlatform(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403);
    }

    private function notifySubmitter($model, string $decision, ?string $notes): void
    {
        if ($model->submitted_by && $user = User::find($model->submitted_by)) {
            $user->notify(new ApprovalReviewed(class_basename($model), $model->name, $decision, $notes));
        }
    }
}
```

```php
// app/Notifications/ApprovalReviewed.php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ApprovalReviewed extends Notification
{
    public function __construct(
        public string $type,
        public string $name,
        public string $decision,
        public ?string $notes,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "{$this->type} \"{$this->name}\" {$this->decision}",
            'notes' => $this->notes,
            'decision' => $this->decision,
        ];
    }
}
```

```php
// app/Notifications/ApprovalSubmitted.php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ApprovalSubmitted extends Notification
{
    public function __construct(public string $type, public string $name, public string $submitter) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "{$this->type} \"{$this->name}\" submitted for approval",
            'submitter' => $this->submitter,
        ];
    }
}
```

In `HotelController@submit` (and the Venue/Package `submit` methods), after `$model->submit(...)` add:

```php
        \App\Models\User::whereNull('tenant_id')->role('super_admin')->get()
            ->each->notify(new \App\Notifications\ApprovalSubmitted(class_basename($hotel), $hotel->name, request()->user()->name));
```

(Use the correct local variable — `$hotel`, `$venue`, `$package`.)

In `routes/web.php`, inside the existing `Route::middleware('role:super_admin')->group(...)` platform block, add:

```php
        Route::get('/approvals', [App\Http\Controllers\Admin\ApprovalController::class, 'index'])->name('admin.approvals.index');
        Route::post('/approvals/{type}/{id}/approve', [App\Http\Controllers\Admin\ApprovalController::class, 'approve'])->name('admin.approvals.approve');
        Route::post('/approvals/{type}/{id}/reject', [App\Http\Controllers\Admin\ApprovalController::class, 'reject'])->name('admin.approvals.reject');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ApprovalReviewTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/ApprovalController.php app/Notifications/ tests/Feature/Admin/ApprovalReviewTest.php routes/web.php app/Http/Controllers/Admin/HotelController.php app/Http/Controllers/Admin/VenueController.php app/Http/Controllers/Admin/PackageController.php
git commit -m "feat(hotels): super-admin approvals queue + review + notifications"
```

---

## Task 9: Gating — public site, quotation selectors, API

**Files:**
- Modify: `app/Http/Controllers/VenueController.php`, `PackageController.php` (public)
- Modify: `routes/web.php` home closure
- Modify: `app/Http/Controllers/Admin/QuotationController.php`
- Modify: `app/Http/Controllers/Api/V1/Admin/VenueController.php`, `PackageController.php`
- Test: `tests/Feature/ApprovalGatingTest.php`

**Interfaces:**
- Consumes: `scopeApproved`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/ApprovalGatingTest.php
<?php

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public venues listing shows approved only', function () {
    $tenant = Tenant::factory()->create();
    $hotel = Hotel::factory()->for($tenant)->approved()->create();
    Venue::factory()->for($tenant)->create(['name' => 'Approved Hall', 'hotel_id' => $hotel->id, 'approval_status' => 'approved', 'status' => 'active']);
    Venue::factory()->for($tenant)->create(['name' => 'Pending Hall', 'hotel_id' => $hotel->id, 'approval_status' => 'pending', 'status' => 'active']);

    $res = $this->get('/venues');
    $res->assertSee('Approved Hall');
    $res->assertDontSee('Pending Hall');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ApprovalGatingTest`
Expected: FAIL (pending venue is shown).

- [ ] **Step 3: Apply `->approved()` to public/selector reads**

- Public `App\Http\Controllers\VenueController@index/@show`: add `->approved()` to the venue query and constrain to venues whose hotel is approved, e.g. `Venue::approved()->whereHas('hotel', fn ($q) => $q->approved())`. If a venue may have null hotel (legacy), also allow `orWhereNull('hotel_id')` **within the same query builder** (not on tenant scope) — keep it simple: `->where(fn($q)=>$q->whereNull('hotel_id')->orWhereHas('hotel', fn($h)=>$h->approved()))`.
- Public `PackageController@index`: `Package::approved()`.
- `routes/web.php` home closure: change `Venue::active()` to `Venue::active()->approved()` for `$featuredVenues`, `$hallCount`, `$hotelCount`.
- `Admin/QuotationController@create`: wherever it loads venues/packages for the builder, add `->approved()` so only approved items are selectable.
- API `Api/V1/Admin/VenueController@index` / `PackageController@index`: add `->approved()` to the listing used by non-privileged consumers (keep manager-facing endpoints unchanged if they must show own drafts — for this scope, apply `->approved()` to the public list method only).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ApprovalGatingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/VenueController.php app/Http/Controllers/PackageController.php routes/web.php app/Http/Controllers/Admin/QuotationController.php app/Http/Controllers/Api/V1/Admin/VenueController.php app/Http/Controllers/Api/V1/Admin/PackageController.php tests/Feature/ApprovalGatingTest.php
git commit -m "feat(hotels): gate public + selectable reads to approved items"
```

---

## Task 10: Vue pages — manager Hotels + super-admin Approvals

**Files:**
- Create: `resources/js/Pages/Hotels/Index.vue`, `Create.vue`, `Edit.vue`
- Create: `resources/js/Pages/Admin/Approvals/Index.vue`
- Modify: `resources/js/Layouts/AppLayout.vue` (nav)

**Interfaces:**
- Consumes: routes `admin.hotels.*`, `admin.hotels.submit`, `admin.venues.submit`, `admin.packages.submit`, `admin.approvals.*`.

- [ ] **Step 1: Build the manager Hotels Index**

Follow the existing `resources/js/Pages/Venues/Index.vue` layout conventions (AppLayout, Link, pagination, StatusBadge). Minimal working page:

```vue
<!-- resources/js/Pages/Hotels/Index.vue -->
<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
defineProps({ hotels: Object, filters: Object });
const badge = (s) => ({
  draft: 'bg-surface-sunken text-ink-subtle', pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-emerald-100 text-emerald-700', rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-surface-sunken text-ink-subtle');
const submit = (h) => router.post(`/admin/hotels/${h.id}/submit`);
</script>
<template>
  <AppLayout title="Hotels">
    <div class="max-w-5xl mx-auto space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-ink">Hotels</h2>
        <Link href="/admin/hotels/create" class="px-4 py-2 text-sm text-white rounded-lg" style="background-color:var(--color-primary)">+ New Hotel</Link>
      </div>
      <div class="bg-surface rounded-lg shadow-sm divide-y divide-border">
        <div v-for="h in hotels.data" :key="h.id" class="flex items-center justify-between p-4">
          <div>
            <Link :href="`/admin/hotels/${h.id}/edit`" class="font-medium text-ink">{{ h.name }}</Link>
            <div class="text-sm text-ink-subtle">{{ h.city }} · {{ h.venues_count }} venues · {{ h.packages_count }} packages</div>
            <p v-if="h.approval_status==='rejected' && h.review_notes" class="mt-1 text-xs text-red-600">Rejected: {{ h.review_notes }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="badge(h.approval_status)">
              {{ h.approval_status }}<span v-if="h.changes_pending_review"> · changes pending</span>
            </span>
            <button v-if="['draft','rejected'].includes(h.approval_status)" @click="submit(h)"
              class="text-sm font-medium text-primary">Submit</button>
          </div>
        </div>
        <p v-if="!hotels.data.length" class="p-6 text-center text-ink-subtle text-sm">No hotels yet.</p>
      </div>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 2: Build Hotels Create/Edit**

Create `Hotels/Create.vue` with a `useForm({ name, city, address, description, star_rating })` posting to `/admin/hotels` (mirror `Venues/Create.vue` field styling). Create `Hotels/Edit.vue` that PUTs to `/admin/hotels/{id}`, lists the hotel's `venues` and `packages` with per-row status badge + a Submit button (POST `admin.venues.submit`/`admin.packages.submit`), and shows `review_notes` when rejected. Reuse the `badge()` helper.

- [ ] **Step 3: Build the super-admin Approvals Index**

```vue
<!-- resources/js/Pages/Admin/Approvals/Index.vue -->
<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
defineProps({ items: Array });
const approve = (i) => router.post(`/admin/approvals/${i.type}/${i.id}/approve`);
const reject = (i) => {
  const notes = window.prompt('Reason for rejection:');
  if (notes) router.post(`/admin/approvals/${i.type}/${i.id}/reject`, { notes });
};
</script>
<template>
  <AppLayout title="Approvals">
    <div class="max-w-5xl mx-auto space-y-4">
      <h2 class="text-xl font-semibold text-ink">Pending Approvals</h2>
      <div class="bg-surface rounded-lg shadow-sm divide-y divide-border">
        <div v-for="i in items" :key="i.type+'-'+i.id" class="flex items-center justify-between p-4">
          <div>
            <span class="text-xs uppercase tracking-wide text-ink-subtle">{{ i.type }}</span>
            <div class="font-medium text-ink">{{ i.name }}</div>
            <div class="text-sm text-ink-subtle">
              by {{ i.submitted_by ?? '—' }}
              <span v-if="i.changes_pending_review" class="text-amber-600">· changes pending review</span>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button @click="approve(i)" class="text-sm font-medium text-emerald-600">Approve</button>
            <button @click="reject(i)" class="text-sm font-medium text-red-600">Reject</button>
          </div>
        </div>
        <p v-if="!items.length" class="p-6 text-center text-ink-subtle text-sm">Nothing awaiting review.</p>
      </div>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 4: Add nav links**

In `resources/js/Layouts/AppLayout.vue`, add a "Hotels" sidebar item (visible when the user has `hotels.view`, following the existing permission-gating pattern for nav items) linking to `/admin/hotels`, and an "Approvals" item in the super-admin section linking to `/admin/approvals` (visible to super_admin). Match the existing nav item markup/icon pattern.

- [ ] **Step 5: Build and commit**

```bash
npm run build
git add resources/js/Pages/Hotels resources/js/Pages/Admin/Approvals resources/js/Layouts/AppLayout.vue
git commit -m "feat(hotels): manager Hotels UI + super-admin Approvals UI + nav"
```

---

## Task 11: Seeders — demo hotels + pending samples

**Files:**
- Modify: `database/seeders/DemoDataSeeder.php` (or `ShowcaseSeeder.php` — whichever creates venues/packages)
- Test: `tests/Feature/Admin/ReportSmokeTest.php` and full suite stay green.

**Interfaces:**
- Produces: seeded hotels linked to venues/packages, all `approved`; at least one `pending` and one `rejected` sample so the approvals queue demos.

- [ ] **Step 1: Update the seeder**

In the seeder that creates venues, first create a `Hotel` per hotel-name prefix (tenant-scoped, `approval_status => 'approved'`), assign `hotel_id` when creating each venue, and set venues/packages `approval_status => 'approved'`. After the approved data, add one `Hotel::factory()->for($tenant)->pending()->create()` and one `->rejected()` plus a `pending` venue, so `/admin/approvals` shows sample items. Keep the existing venue/package data otherwise unchanged.

- [ ] **Step 2: Run the seeder against a scratch DB and the full suite**

Run: `php artisan migrate:fresh --seed --env=testing` (or run the seeder test), then `php artisan test`
Expected: all green; demo DB untouched (tests use `:memory:`).

- [ ] **Step 3: Commit**

```bash
git add database/seeders
git commit -m "feat(hotels): seed demo hotels + pending/rejected approval samples"
```

---

## Task 12: Recovery baseline, docs, and full doctor run

**Files:**
- Modify: `docs/admin-guide.md`, `DEMO-README.md`
- Regenerate: `tools/demo/baseline/manifest.json` + `tools/demo/baseline/snapshot/**`

- [ ] **Step 1: Document the workflow**

Add an "Approval workflow" subsection to `docs/admin-guide.md` (manager submits hotels/venues/packages; super admin approves in `/admin/approvals`; only approved items are public/selectable; edits to approved items stay live but re-queue). Add a one-line note to `DEMO-README.md` pointing at `/admin/approvals`.

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test`
Expected: all pass (existing + new).

- [ ] **Step 3: Regenerate the recovery baseline**

Run: `php artisan dev:baseline --force`
This rewrites `tools/demo/baseline/manifest.json` (sha256s) and refreshes `snapshot/` so the new source files (Hotel model, Approvable trait, observer, controllers, notifications, migrations, seeders, Vue pages) become part of the known-good baseline that `dev:doctor` verifies and `dev:restore` can restore.

- [ ] **Step 4: Run the doctor to confirm HEALTHY**

Run: `php artisan dev:doctor`
Expected: `RESULT: HEALTHY - code matches baseline and all flows pass.`

- [ ] **Step 5: Commit**

```bash
git add docs/admin-guide.md DEMO-README.md tools/demo/baseline
git commit -m "docs+tooling(hotels): admin-guide, DEMO-README, re-baseline dev:doctor"
```

---

## Self-Review

- **Spec coverage:** Data model (T1–T3), state machine + observer (T2), gating (T9), permissions (T4), manager UI (T6,T7,T10), super-admin queue + notifications (T8,T10), data migration (T5), recovery tool + everywhere touchpoints (T9 API, T11 seeders, T12 baseline/docs, T10 nav), testing (each task). All spec sections mapped.
- **Placeholder scan:** No TBD/TODO; each code step contains real code. Vue Create/Edit and nav steps (T10 S2/S4) describe concrete changes against a named existing file to mirror; acceptable since they replicate an in-repo pattern verbatim.
- **Type consistency:** transition helpers `submit/approve/reject`, scopes `approved/pendingReview`, route names `admin.hotels.*`/`admin.approvals.*`, and `type ∈ hotel|venue|package` are used consistently across tasks.
