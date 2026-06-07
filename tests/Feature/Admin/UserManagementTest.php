<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->superAdmin = User::factory()
            ->state(['role' => 'super_admin'])
            ->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_super_admin_can_view_user_list(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/users')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Users/Index')->has('stats')->has('users'));
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users/create')->assertForbidden();
    }

    public function test_super_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'name'                  => 'New Manager',
            'email'                 => 'manager@example.com',
            'role'                  => 'manager',
            'tenant_id'             => $this->tenant->id,
            'is_active'             => true,
            'password'              => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', ['email' => 'manager@example.com', 'role' => 'manager']);

        $created = User::withoutTenantScope()->where('email', 'manager@example.com')->first();
        $this->assertTrue($created->hasRole('manager'));
    }

    public function test_super_admin_can_update_user(): void
    {
        $user = User::factory()->state(['role' => 'staff'])->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->superAdmin)->put("/admin/users/{$user->id}", [
            'name'      => 'Promoted',
            'email'     => $user->email,
            'role'      => 'manager',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('manager', $user->role);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('staff'));
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->superAdmin)->put("/admin/users/{$user->id}/reset-password", [
            'password'              => 'brand-new-pass-9',
            'password_confirmation' => 'brand-new-pass-9',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass-9', $user->fresh()->password));
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete("/admin/users/{$this->superAdmin->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }
}
