<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Tenant;
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
}
