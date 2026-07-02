<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_venues_index_returns_200(): void
    {
        Venue::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/venues');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_public_venues_index_returns_only_active_venues(): void
    {
        Venue::factory()->count(2)->create(['status' => 'active']);
        Venue::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/venues');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_public_venue_show_returns_200(): void
    {
        $venue = Venue::factory()->create();

        // Venues are bound by slug (see Venue::getRouteKeyName()).
        $response = $this->getJson("/api/v1/venues/{$venue->slug}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $venue->id);
    }

    public function test_public_venue_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/v1/venues/99999');

        $response->assertStatus(404);
    }

    public function test_admin_venues_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/admin/venues');

        $response->assertStatus(401);
    }

    public function test_admin_venues_index_requires_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/venues');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_venues(): void
    {
        $admin = User::factory()->admin()->create();
        Venue::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/venues');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_venue(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'name' => 'Grand Ballroom',
            'description' => 'A beautiful ballroom.',
            'capacity_min' => 50,
            'capacity_max' => 300,
            'base_price' => 5000.00,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/venues', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Grand Ballroom');
        $this->assertDatabaseHas('venues', ['name' => 'Grand Ballroom']);
    }

    public function test_admin_can_update_venue(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->create(['tenant_id' => $admin->tenant_id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/venues/{$venue->slug}", [
                'name' => 'Updated Name',
                'capacity_min' => $venue->capacity_min,
                'capacity_max' => $venue->capacity_max,
                'base_price' => $venue->base_price,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_admin_can_delete_venue(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->create(['tenant_id' => $admin->tenant_id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/venues/{$venue->slug}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_venue_creation_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/venues', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'capacity_min', 'capacity_max', 'base_price']);
    }
}
