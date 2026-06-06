<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_vendors(): void
    {
        $admin = User::factory()->admin()->create();
        Vendor::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/vendors');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_vendor(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'name' => 'Flowers by Rosa',
            'category' => 'florist',
            'email' => 'rosa@flowers.test',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/vendors', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Flowers by Rosa');
    }

    public function test_admin_can_show_vendor(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/vendors/{$vendor->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $vendor->id);
    }

    public function test_admin_can_delete_vendor_without_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/vendors/{$vendor->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_unauthenticated_cannot_access_vendors(): void
    {
        $response = $this->getJson('/api/v1/admin/vendors');
        $response->assertStatus(401);
    }
}
