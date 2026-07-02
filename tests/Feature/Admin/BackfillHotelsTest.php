<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillHotelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify the backfill migration groups venues by the name prefix before
     * ' — ' into a single Hotel row per tenant and marks everything approved.
     *
     * Note: Artisan::call('migrate', ['--path' => ...]) cannot re-run a
     * migration that RefreshDatabase already applied (on empty tables) before
     * the test body runs, so we require + call up() directly to test the
     * backfill logic against the factory data.
     */
    public function test_backfill_groups_venues_by_name_prefix_into_approved_hotels(): void
    {
        $tenant = Tenant::factory()->create();

        Venue::factory()->for($tenant)->create([
            'name'            => 'Grand Palace — Ballroom',
            'approval_status' => 'approved',
            'hotel_id'        => null,
        ]);
        Venue::factory()->for($tenant)->create([
            'name'            => 'Grand Palace — Terrace',
            'approval_status' => 'approved',
            'hotel_id'        => null,
        ]);

        // Run the migration logic directly (bypasses the migrations-table guard).
        // The migration filename matches the --path used in the brief's Pest original:
        // database/migrations/2026_07_02_000004_backfill_hotels_from_venues.php
        $migration = require database_path('migrations/2026_07_02_000004_backfill_hotels_from_venues.php');
        $migration->up();

        $this->assertSame(
            1,
            Hotel::withoutGlobalScopes()->where('name', 'Grand Palace')->count(),
            'Expected exactly one Hotel named "Grand Palace"'
        );
        $this->assertSame(
            0,
            Venue::withoutGlobalScopes()->whereNull('hotel_id')->count(),
            'Expected all venues to have hotel_id set after backfill'
        );
        $this->assertSame(
            'approved',
            Hotel::withoutGlobalScopes()->where('name', 'Grand Palace')->first()->approval_status,
            'Backfilled hotel should be approved'
        );
    }
}
