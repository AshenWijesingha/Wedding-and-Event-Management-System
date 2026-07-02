<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->state(['role' => 'super_admin'])->create(['tenant_id' => null]);
    }

    public function test_super_admin_can_view_tenants(): void
    {
        Tenant::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get('/admin/tenants')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Tenants/Index')->has('tenants')->has('stats'));
    }

    public function test_non_super_admin_cannot_access_tenant_management(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $tenant->id]);
        $admin = User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->get('/admin/tenants')->assertForbidden();
        $this->actingAs($admin)->get('/admin/tenants')->assertForbidden();
    }

    public function test_super_admin_creates_tenant_and_provisions_owner(): void
    {
        $this->actingAs($this->superAdmin)->post('/admin/tenants', [
            'name' => 'Grand Hotel',
            'slug' => 'grand-hotel',
            'status' => 'active',
            'owner_name' => 'Olivia Owner',
            'owner_email' => 'olivia@grandhotel.test',
            'owner_password' => 'secret-pass-123',
            'owner_password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/tenants');

        $tenant = Tenant::where('slug', 'grand-hotel')->first();
        $this->assertNotNull($tenant);

        $owner = User::withoutTenantScope()->where('email', 'olivia@grandhotel.test')->first();
        $this->assertNotNull($owner);
        $this->assertSame('tenant_owner', $owner->role);
        $this->assertSame($tenant->id, $owner->tenant_id);
        $this->assertTrue($owner->hasRole('tenant_owner'));
    }

    public function test_tenant_creation_requires_owner(): void
    {
        $this->actingAs($this->superAdmin)->post('/admin/tenants', [
            'name' => 'No Owner Co',
            'slug' => 'no-owner',
            'status' => 'active',
        ])->assertSessionHasErrors(['owner_name', 'owner_email', 'owner_password']);

        $this->assertDatabaseMissing('tenants', ['slug' => 'no-owner']);
    }

    public function test_super_admin_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial']);

        $this->actingAs($this->superAdmin)->put("/admin/tenants/{$tenant->id}", [
            'name' => 'Renamed',
            'slug' => $tenant->slug,
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $tenant->refresh();
        $this->assertSame('Renamed', $tenant->name);
        $this->assertSame('active', $tenant->status);
    }

    public function test_tenant_with_bookings_cannot_be_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        Booking::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($this->superAdmin)
            ->delete("/admin/tenants/{$tenant->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_tenant_without_bookings_is_deleted_with_its_users(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($this->superAdmin)
            ->delete("/admin/tenants/{$tenant->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
    }
}
