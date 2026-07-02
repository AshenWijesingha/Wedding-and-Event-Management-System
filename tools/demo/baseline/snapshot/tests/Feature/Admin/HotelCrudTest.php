<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelCrudTest extends TestCase
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

    public function test_owner_creates_a_hotel_as_draft_then_submits_it(): void
    {
        $this->actingAs($this->owner)
            ->post('/admin/hotels', ['name' => 'Ocean Pearl', 'city' => 'Galle'])
            ->assertRedirect();

        $hotel = Hotel::where('name', 'Ocean Pearl')->first();
        $this->assertNotNull($hotel, 'Hotel should exist after creation');
        $this->assertSame('draft', $hotel->approval_status);

        $this->actingAs($this->owner)
            ->post("/admin/hotels/{$hotel->slug}/submit")
            ->assertRedirect();

        $this->assertSame('pending', $hotel->fresh()->approval_status);
    }
}
