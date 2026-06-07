<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_guest_cannot_access_venues_index(): void
    {
        $response = $this->get('/admin/venues');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_venues_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/venues');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Venues/Index'));
    }

    public function test_admin_can_view_venue_create(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/venues/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Venues/Create'));
    }

    public function test_admin_can_create_venue(): void
    {
        $payload = [
            'name' => 'Grand Ballroom',
            'description' => 'Lovely venue',
            'capacity_min' => 50,
            'capacity_max' => 300,
            'base_price' => 5000,
            'weekend_surcharge' => 500,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)
            ->post('/admin/venues', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('venues', ['name' => 'Grand Ballroom']);
    }

    public function test_venue_creation_requires_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/venues', [
                'capacity_min' => 50,
                'capacity_max' => 300,
                'base_price' => 5000,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_view_venue_edit(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/admin/venues/{$venue->slug}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Venues/Edit'));
    }

    public function test_admin_can_update_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put("/admin/venues/{$venue->slug}", [
                'name' => 'Updated Name',
                'capacity_min' => 50,
                'capacity_max' => 300,
                'base_price' => 6000,
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/admin/venues/{$venue->slug}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }
}
