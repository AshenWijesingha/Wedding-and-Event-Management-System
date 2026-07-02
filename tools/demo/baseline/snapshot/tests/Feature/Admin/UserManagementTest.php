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
            ->assertInertia(fn ($p) => $p->component('Users/Index')
                ->where('systemWide', true)
                ->has('stats')->has('users'));
    }

    public function test_super_admin_view_spans_all_tenants(): void
    {
        $other = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $other->id]);

        $expected = User::withoutTenantScope()->count();

        $this->actingAs($this->superAdmin)
            ->get('/admin/users')
            ->assertInertia(fn ($p) => $p->has('users.data', $expected));
    }

    public function test_admin_can_view_only_own_tenant_users(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

        $other = Tenant::factory()->create();
        $foreign = User::factory()->create(['tenant_id' => $other->id]);

        $ownCount = User::withoutTenantScope()->where('tenant_id', $this->tenant->id)->count();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Users/Index')
                ->where('systemWide', false)
                ->has('users.data', $ownCount)
                ->where('users.data', fn ($rows) => collect($rows)->doesntContain('id', $foreign->id)));
    }

    public function test_guest_roles_cannot_access_user_management(): void
    {
        $manager = User::factory()->manager()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
        $this->actingAs($manager)->get('/admin/users/create')->assertForbidden();
    }

    public function test_super_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'name' => 'New Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', ['email' => 'manager@example.com', 'role' => 'manager']);

        $created = User::withoutTenantScope()->where('email', 'manager@example.com')->first();
        $this->assertTrue($created->hasRole('manager'));
    }

    public function test_super_admin_can_create_super_admin(): void
    {
        $this->actingAs($this->superAdmin)->post('/admin/users', [
            'name' => 'Root',
            'email' => 'root@example.com',
            'role' => 'super_admin',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'root@example.com', 'role' => 'super_admin']);
    }

    public function test_admin_can_create_user_in_own_tenant(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Tenant Staff',
            'email' => 'staff@example.com',
            'role' => 'staff',
            'is_active' => true,
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'email' => 'staff@example.com',
            'role' => 'staff',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_admin_cannot_create_super_admin(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Escalated',
            'email' => 'escalate@example.com',
            'role' => 'super_admin',
            'is_active' => true,
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'escalate@example.com']);
    }

    public function test_admin_store_pins_user_to_own_tenant(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);
        $other = Tenant::factory()->create();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Pinned',
            'email' => 'pinned@example.com',
            'role' => 'staff',
            'tenant_id' => $other->id, // foreign tenant should be ignored
            'is_active' => true,
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasNoErrors();

        $created = User::withoutTenantScope()->where('email', 'pinned@example.com')->first();
        $this->assertSame($this->tenant->id, $created->tenant_id);
    }

    public function test_admin_cannot_manage_super_admin_in_same_tenant(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($admin)->get("/admin/users/{$this->superAdmin->id}/edit")->assertForbidden();
        $this->actingAs($admin)->delete("/admin/users/{$this->superAdmin->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_admin_cannot_access_other_tenant_user(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);
        $other = Tenant::factory()->create();
        $foreign = User::factory()->create(['tenant_id' => $other->id]);

        // A foreign-tenant user is hidden by the tenant scope (404, not 403).
        $this->actingAs($admin)->get("/admin/users/{$foreign->id}/edit")->assertNotFound();

        // Deletion requires users.delete, which admins lack. Use a tenant owner (who
        // does have it) so this still proves cross-tenant records are invisible (404)
        // rather than merely permission-blocked.
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($owner)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete("/admin/users/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_admin_cannot_promote_existing_user_to_super_admin(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);
        $user = User::factory()->state(['role' => 'staff'])->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'super_admin',
            'is_active' => true,
        ])->assertSessionHasErrors('role');

        $this->assertSame('staff', $user->fresh()->role);
    }

    public function test_super_admin_can_update_user(): void
    {
        $user = User::factory()->state(['role' => 'staff'])->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->superAdmin)->put("/admin/users/{$user->id}", [
            'name' => 'Promoted',
            'email' => $user->email,
            'role' => 'manager',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('manager', $user->role);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('staff'));
    }

    public function test_actor_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'staff',
            'is_active' => true,
        ])->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->superAdmin)->put("/admin/users/{$user->id}/reset-password", [
            'password' => 'brand-new-pass-9',
            'password_confirmation' => 'brand-new-pass-9',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-pass-9', $user->fresh()->password));
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $this->actingAs($this->superAdmin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete("/admin/users/{$this->superAdmin->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }
}
