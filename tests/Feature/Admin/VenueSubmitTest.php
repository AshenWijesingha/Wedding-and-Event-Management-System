<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueSubmitTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->for($this->tenant)->create();
        $this->owner->assignRole('tenant_owner');
    }

    public function test_new_venue_is_draft_and_can_be_submitted(): void
    {
        $hotel = Hotel::factory()->for($this->tenant)->approved()->create();

        $this->actingAs($this->owner)->post('/admin/venues', [
            'name'         => 'Sky Hall',
            'hotel_id'     => $hotel->id,
            'capacity_min' => 50,
            'capacity_max' => 300,
            'base_price'   => 1000,
        ])->assertRedirect();

        $venue = Venue::where('name', 'Sky Hall')->first();
        $this->assertNotNull($venue, 'Venue should exist after creation');
        $this->assertSame('draft', $venue->approval_status);
        $this->assertSame($hotel->id, $venue->hotel_id);

        $this->actingAs($this->owner)
            ->post("/admin/venues/{$venue->slug}/submit")
            ->assertRedirect();

        $this->assertSame('pending', $venue->fresh()->approval_status);
    }

    public function test_cross_tenant_hotel_id_is_rejected(): void
    {
        $tenantB = Tenant::factory()->create();
        $hotelB  = Hotel::factory()->for($tenantB)->approved()->create();

        $this->actingAs($this->owner)->post('/admin/venues', [
            'name'         => 'Stolen Hall',
            'hotel_id'     => $hotelB->id,
            'capacity_min' => 50,
            'capacity_max' => 300,
            'base_price'   => 1000,
        ])->assertSessionHasErrors('hotel_id');

        $this->assertNull(Venue::where('name', 'Stolen Hall')->first());
    }
}
