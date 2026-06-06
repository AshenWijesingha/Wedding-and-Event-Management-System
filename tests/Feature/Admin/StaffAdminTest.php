<?php

namespace Tests\Feature\Admin;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_staff_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/staff');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Staff/Index'));
    }

    public function test_admin_can_view_staff_create(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/staff/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Staff/Create'));
    }

    public function test_admin_can_create_staff(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', [
                'first_name' => 'Alice',
                'last_name' => 'Smith',
                'email' => 'alice@eventpro.test',
                'role' => 'coordinator',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('staff', ['email' => 'alice@eventpro.test']);
    }

    public function test_admin_can_view_staff_show(): void
    {
        $staff = Staff::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/admin/staff/{$staff->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Staff/Show'));
    }

    public function test_admin_can_delete_staff(): void
    {
        $staff = Staff::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/admin/staff/{$staff->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
    }

    public function test_guest_cannot_view_staff(): void
    {
        $response = $this->get('/admin/staff');
        $response->assertRedirect('/login');
    }
}
