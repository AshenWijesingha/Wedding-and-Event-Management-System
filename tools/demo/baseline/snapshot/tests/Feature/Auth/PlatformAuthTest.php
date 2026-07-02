<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * The platform super admin has tenant_id = NULL. When a tenant is current
 * (e.g. single-tenancy resolves one on every request, including the login page)
 * the BelongsToTenant scope must NOT hide them from authentication, while still
 * keeping ordinary data access strictly tenant-isolated.
 */
class PlatformAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a platform user (tenant_id NULL) BEFORE any tenant is current, so the
     * BelongsToTenant `creating` hook does not auto-fill tenant_id, then make a
     * tenant current to simulate a normal request context.
     */
    private function platformSuperAdminWithTenantCurrent(array $attributes = []): User
    {
        Tenant::forgetCurrent();

        $superAdmin = User::factory()->state(['role' => 'super_admin'])
            ->create(array_merge(['tenant_id' => null], $attributes));

        Tenant::factory()->create()->makeCurrent();

        $this->assertNull($superAdmin->fresh()->tenant_id, 'precondition: platform user must have NULL tenant_id');

        return $superAdmin;
    }

    public function test_platform_super_admin_can_log_in_while_a_tenant_is_current(): void
    {
        $superAdmin = $this->platformSuperAdminWithTenantCurrent(['password' => Hash::make('Admin@123')]);

        $this->post('/login', [
            'email' => $superAdmin->email,
            'password' => 'Admin@123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_password_broker_finds_platform_super_admin_while_a_tenant_is_current(): void
    {
        $superAdmin = $this->platformSuperAdminWithTenantCurrent();

        $this->assertNotNull(Password::broker()->getUser(['email' => $superAdmin->email]));
    }

    public function test_tenant_scope_still_hides_platform_users_from_ordinary_queries(): void
    {
        $superAdmin = $this->platformSuperAdminWithTenantCurrent();

        // Strict isolation: a normal (scoped) query must not surface the null-tenant user.
        $this->assertFalse(User::where('id', $superAdmin->id)->exists());
        $this->assertTrue(User::withoutTenantScope()->where('id', $superAdmin->id)->exists());
    }
}
