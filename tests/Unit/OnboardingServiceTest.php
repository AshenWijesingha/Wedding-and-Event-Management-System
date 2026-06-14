<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): OnboardingService
    {
        return app(OnboardingService::class);
    }

    /** A tenant with branding intentionally incomplete (default color, no logo/settings). */
    private function freshTenant(): Tenant
    {
        return Tenant::factory()->create([
            'primary_color' => '#6366f1',
            'logo'          => null,
            'settings'      => [],
        ]);
    }

    public function test_progress_flips_each_step_as_data_appears(): void
    {
        $tenant = $this->freshTenant();
        $svc = $this->service();

        $p = $svc->progress($tenant);
        $this->assertFalse($p['branding']);
        $this->assertFalse($p['venue']);
        $this->assertFalse($p['package']);
        $this->assertFalse($p['team']);
        $this->assertFalse($p['completed']);

        $tenant->setSetting('branding', ['company_name' => 'Acme Events']);
        Venue::factory()->create(['tenant_id' => $tenant->id]);
        Package::factory()->create(['tenant_id' => $tenant->id]);
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

        $p = $svc->progress($tenant->fresh());
        $this->assertTrue($p['branding']);
        $this->assertTrue($p['venue']);
        $this->assertTrue($p['package']);
        $this->assertTrue($p['team']);
        $this->assertTrue($p['completed']);
    }

    public function test_branding_done_via_custom_primary_color(): void
    {
        $tenant = $this->freshTenant();
        $this->assertFalse($this->service()->progress($tenant)['branding']);

        $tenant->update(['primary_color' => '#ff0066']);
        $this->assertTrue($this->service()->progress($tenant->fresh())['branding']);
    }

    public function test_state_reflects_dismiss_and_seen(): void
    {
        $tenant = $this->freshTenant();
        $svc = $this->service();

        $state = $svc->state($tenant);
        $this->assertFalse($state['seen']);
        $this->assertFalse($state['dismissed']);
        $this->assertTrue($state['show_checklist']);

        $svc->markSeen($tenant);
        $this->assertTrue($svc->state($tenant->fresh())['seen']);

        $svc->dismiss($tenant->fresh());
        $state = $svc->state($tenant->fresh());
        $this->assertTrue($state['dismissed']);
        $this->assertFalse($state['show_checklist']);
    }

    public function test_team_ignores_client_and_owner_roles(): void
    {
        $tenant = $this->freshTenant();
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'tenant_owner']);
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'client']);

        $this->assertFalse($this->service()->progress($tenant)['team']);

        User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'staff']);
        $this->assertTrue($this->service()->progress($tenant)['team']);
    }
}
