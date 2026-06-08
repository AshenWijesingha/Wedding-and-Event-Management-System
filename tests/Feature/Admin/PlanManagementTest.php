<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->state(['role' => 'super_admin'])->create(['tenant_id' => null]);
    }

    public function test_super_admin_can_view_plans(): void
    {
        Plan::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get('/admin/plans')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Plans/Index')->has('plans'));
    }

    public function test_non_super_admin_cannot_manage_plans(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->get('/admin/plans')->assertForbidden();
    }

    public function test_super_admin_can_create_plan_with_features_and_limits(): void
    {
        $this->actingAs($this->superAdmin)->post('/admin/plans', [
            'name'          => 'Professional',
            'slug'          => 'professional',
            'price_monthly' => 49,
            'price_yearly'  => 490,
            'features'      => ['Unlimited reports', '', 'Custom branding'],
            'limits'        => ['users' => 10, 'venues' => '', 'bookings' => 500],
            'is_active'     => true,
            'sort_order'    => 1,
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/plans');

        $plan = Plan::where('slug', 'professional')->first();
        $this->assertNotNull($plan);
        // Empty feature dropped, real ones kept.
        $this->assertSame(['Unlimited reports', 'Custom branding'], $plan->features);
        // Blank limit dropped; numeric limits cast to int.
        $this->assertSame(['users' => 10, 'bookings' => 500], $plan->limits);
    }

    public function test_super_admin_can_update_plan(): void
    {
        $plan = Plan::factory()->create(['name' => 'Old', 'price_monthly' => 10]);

        $this->actingAs($this->superAdmin)->put("/admin/plans/{$plan->id}", [
            'name'          => 'New',
            'slug'          => $plan->slug,
            'price_monthly' => 20,
            'price_yearly'  => 200,
            'features'      => ['A'],
            'limits'        => ['users' => 5],
            'is_active'     => true,
            'sort_order'    => 0,
        ])->assertSessionHasNoErrors();

        $plan->refresh();
        $this->assertSame('New', $plan->name);
        $this->assertSame(20.0, (float) $plan->price_monthly);
    }

    public function test_plan_with_tenants_cannot_be_deleted(): void
    {
        $plan = Plan::factory()->create();
        Tenant::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($this->superAdmin)
            ->delete("/admin/plans/{$plan->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_empty_plan_can_be_deleted(): void
    {
        $plan = Plan::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete("/admin/plans/{$plan->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }
}
