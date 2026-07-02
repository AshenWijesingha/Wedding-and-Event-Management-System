<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that the full seed produces approved demo venues/hotels and that
 * the pending/rejected approval-queue samples also exist.
 */
class SeederApprovalSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_hotel_hall_venues_are_approved(): void
    {
        // Venue names that come from SriLankaHotels::halls() contain ' — '.
        $unapproved = Venue::withoutGlobalScopes()
            ->where('name', 'like', '% — %')
            ->where('approval_status', '!=', 'approved')
            ->count();

        $this->assertSame(0, $unapproved, 'Every hotel-hall venue should have approval_status=approved');
    }

    public function test_pending_and_rejected_hotel_samples_exist(): void
    {
        $this->assertGreaterThan(
            0,
            Hotel::withoutGlobalScopes()->where('approval_status', 'pending')->count(),
            'At least one pending hotel should exist for the approvals queue'
        );

        $this->assertGreaterThan(
            0,
            Hotel::withoutGlobalScopes()->where('approval_status', 'rejected')->count(),
            'At least one rejected hotel should exist for the approvals queue'
        );
    }

    public function test_pending_venue_sample_exists(): void
    {
        $this->assertGreaterThan(
            0,
            Venue::withoutGlobalScopes()->where('approval_status', 'pending')->count(),
            'At least one pending venue should exist for the approvals queue'
        );
    }

    public function test_hotel_hall_venues_have_hotel_id(): void
    {
        $unlinked = Venue::withoutGlobalScopes()
            ->where('name', 'like', '% — %')
            ->whereNull('hotel_id')
            ->count();

        $this->assertSame(0, $unlinked, 'Every hotel-hall venue should have hotel_id set');
    }
}
