<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_platform_dashboard(): void
    {
        $superAdmin = User::factory()->state(['role' => 'super_admin'])->create(['tenant_id' => null]);
        Tenant::factory()->create();

        $this->actingAs($superAdmin)
            ->get('/admin')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Platform/Dashboard')
                ->has('stats')
                ->has('recentTenants')
                ->has('planDistribution'));
    }

    public function test_tenant_user_sees_tenant_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->setSetting('onboarding', ['seen' => true, 'dismissed' => true]);
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)
            ->get('/admin')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Dashboard'));
    }
}
