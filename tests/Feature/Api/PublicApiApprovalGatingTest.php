<?php

namespace Tests\Feature\Api;

use App\Models\Hotel;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiApprovalGatingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->hotel = Hotel::factory()->for($this->tenant)->approved()->create();
    }

    // ── Venues ──────────────────────────────────────────────────────────────

    public function test_public_api_venues_index_returns_approved_venue(): void
    {
        Venue::factory()->for($this->tenant)->create([
            'name' => 'Approved Hall',
            'hotel_id' => $this->hotel->id,
            'approval_status' => 'approved',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/venues');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Approved Hall']);
    }

    public function test_public_api_venues_index_excludes_pending_venue(): void
    {
        Venue::factory()->for($this->tenant)->create([
            'name' => 'Pending Hall',
            'hotel_id' => $this->hotel->id,
            'approval_status' => 'pending',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/venues');

        $response->assertOk();
        $response->assertJsonMissing(['name' => 'Pending Hall']);
    }

    public function test_public_api_venues_index_excludes_venue_with_unapproved_hotel(): void
    {
        $pendingHotel = Hotel::factory()->for($this->tenant)->pending()->create();

        Venue::factory()->for($this->tenant)->create([
            'name' => 'Orphaned Hall',
            'hotel_id' => $pendingHotel->id,
            'approval_status' => 'approved',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/venues');

        $response->assertOk();
        $response->assertJsonMissing(['name' => 'Orphaned Hall']);
    }

    public function test_public_api_venues_show_returns_approved_venue(): void
    {
        $venue = Venue::factory()->for($this->tenant)->create([
            'hotel_id' => $this->hotel->id,
            'approval_status' => 'approved',
            'status' => 'active',
        ]);

        $this->getJson("/api/v1/venues/{$venue->slug}")->assertOk();
    }

    public function test_public_api_venues_show_returns_404_for_pending_venue(): void
    {
        $venue = Venue::factory()->for($this->tenant)->create([
            'hotel_id' => $this->hotel->id,
            'approval_status' => 'pending',
            'status' => 'active',
        ]);

        $this->getJson("/api/v1/venues/{$venue->slug}")->assertNotFound();
    }

    public function test_public_api_venues_show_returns_404_when_hotel_is_pending(): void
    {
        $pendingHotel = Hotel::factory()->for($this->tenant)->pending()->create();

        $venue = Venue::factory()->for($this->tenant)->create([
            'hotel_id' => $pendingHotel->id,
            'approval_status' => 'approved',
            'status' => 'active',
        ]);

        $this->getJson("/api/v1/venues/{$venue->slug}")->assertNotFound();
    }

    // ── Packages ─────────────────────────────────────────────────────────────

    public function test_public_api_packages_index_returns_approved_package(): void
    {
        $pkg = Package::factory()->for($this->tenant)->approved()->create(['name' => 'Gold Package']);

        $response = $this->getJson('/api/v1/packages');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Gold Package']);
    }

    public function test_public_api_packages_index_excludes_pending_package(): void
    {
        Package::factory()->for($this->tenant)->pending()->create(['name' => 'Pending Package']);

        $response = $this->getJson('/api/v1/packages');

        $response->assertOk();
        $response->assertJsonMissing(['name' => 'Pending Package']);
    }

    public function test_public_api_packages_show_returns_approved_package(): void
    {
        $pkg = Package::factory()->for($this->tenant)->approved()->create();

        $this->getJson("/api/v1/packages/{$pkg->slug}")->assertOk();
    }

    public function test_public_api_packages_show_returns_404_for_pending_package(): void
    {
        $pkg = Package::factory()->for($this->tenant)->pending()->create();

        $this->getJson("/api/v1/packages/{$pkg->slug}")->assertNotFound();
    }
}
