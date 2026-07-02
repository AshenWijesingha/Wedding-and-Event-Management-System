<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_venues_listing_shows_approved_only(): void
    {
        $tenant = Tenant::factory()->create();
        $hotel = Hotel::factory()->for($tenant)->approved()->create();

        Venue::factory()->for($tenant)->create([
            'name' => 'Approved Hall',
            'hotel_id' => $hotel->id,
            'approval_status' => 'approved',
            'status' => 'active',
        ]);

        Venue::factory()->for($tenant)->create([
            'name' => 'Pending Hall',
            'hotel_id' => $hotel->id,
            'approval_status' => 'pending',
            'status' => 'active',
        ]);

        $res = $this->get('/venues');
        $res->assertSee('Approved Hall');
        $res->assertDontSee('Pending Hall');
    }

    public function test_admin_booking_create_page_excludes_non_approved_venues_and_packages(): void
    {
        $tenant = Tenant::factory()->create();
        $hotel = Hotel::factory()->for($tenant)->approved()->create();

        $approvedVenue = Venue::factory()->for($tenant)->create([
            'name' => 'Approved Venue',
            'hotel_id' => $hotel->id,
            'approval_status' => 'approved',
            'status' => 'active',
        ]);

        $pendingVenue = Venue::factory()->for($tenant)->create([
            'name' => 'Pending Venue',
            'hotel_id' => $hotel->id,
            'approval_status' => 'pending',
            'status' => 'active',
        ]);

        $approvedPackage = Package::factory()->for($tenant)->approved()->create(['name' => 'Approved Package']);
        $pendingPackage = Package::factory()->for($tenant)->pending()->create(['name' => 'Pending Package']);

        $manager = User::factory()->for($tenant)->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->get('/admin/bookings/create');

        $response->assertOk();
        $response->assertSee('Approved Venue');
        $response->assertDontSee('Pending Venue');
        $response->assertSee('Approved Package');
        $response->assertDontSee('Pending Package');
    }
}
