<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Settings\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PlatformManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        // Platform super admin has no tenant. Create before any tenant is current.
        return User::factory()->state(['role' => 'super_admin'])->create(['tenant_id' => null]);
    }

    public function test_super_admin_can_impersonate_a_tenant_and_return(): void
    {
        $superAdmin = $this->superAdmin();
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($superAdmin)
            ->post("/admin/impersonate/{$tenant->id}")
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($owner);

        $this->post('/admin/impersonate-stop')->assertRedirect('/admin/tenants');
        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_super_admin_can_suspend_and_activate_a_tenant(): void
    {
        $superAdmin = $this->superAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->actingAs($superAdmin)->post("/admin/tenants/{$tenant->id}/suspend");
        $this->assertEquals('suspended', $tenant->fresh()->status);

        $this->actingAs($superAdmin)->post("/admin/tenants/{$tenant->id}/activate");
        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_user_of_suspended_tenant_is_blocked_from_admin(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'suspended']);
        $admin = User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_tenant_update_is_recorded_in_the_audit_log(): void
    {
        $superAdmin = $this->superAdmin();
        $tenant = Tenant::factory()->create(['name' => 'Original Name']);

        $this->actingAs($superAdmin)->put("/admin/tenants/{$tenant->id}", [
            'name' => 'Renamed Co',
            'slug' => $tenant->slug,
            'status' => $tenant->status,
        ]);

        $this->assertTrue(
            Activity::where('log_name', 'tenant')
                ->where('subject_id', $tenant->id)
                ->where('event', 'updated')
                ->exists()
        );
    }

    public function test_super_admin_can_view_audit_log(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->get('/admin/audit-log')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/AuditLog'));
    }

    public function test_super_admin_can_update_platform_settings(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->put('/admin/platform-settings', [
                'platform_name' => 'My Platform',
                'default_plan_id' => null,
                'signups_enabled' => false,
                'support_email' => 'help@example.com',
            ])->assertRedirect();

        $settings = app(PlatformSettings::class);
        $this->assertEquals('My Platform', $settings->platform_name);
        $this->assertFalse($settings->signups_enabled);
    }

    public function test_registration_blocked_when_signups_disabled(): void
    {
        $settings = app(PlatformSettings::class);
        $settings->signups_enabled = false;
        $settings->save();

        $this->post('/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_non_super_admin_cannot_access_platform_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/audit-log')->assertForbidden();
        $this->actingAs($admin)->get('/admin/platform-settings')->assertForbidden();
    }
}
