<?php

namespace Tests\Feature\Api;

use App\Models\Staff;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_staff(): void
    {
        $admin = User::factory()->admin()->create();
        Staff::factory()->count(4)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/staff');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_staff(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'email' => 'alice@eventpro.test',
            'role' => 'coordinator',
            'status' => 'active',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/staff', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.first_name', 'Alice');
    }

    public function test_admin_can_get_staff_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = Staff::factory()->create(['tenant_id' => $admin->tenant_id]);
        Task::factory()->count(3)->create(['assigned_to' => $staff->id, 'tenant_id' => $admin->tenant_id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/staff/{$staff->id}/schedule");

        $response->assertStatus(200);
    }

    public function test_admin_can_delete_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = Staff::factory()->create(['tenant_id' => $admin->tenant_id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/staff/{$staff->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
    }

    public function test_staff_creation_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/staff', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'last_name']);
    }
}
