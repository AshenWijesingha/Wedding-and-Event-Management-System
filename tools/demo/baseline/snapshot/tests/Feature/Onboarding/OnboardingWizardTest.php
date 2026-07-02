<?php

namespace Tests\Feature\Onboarding;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    /** Tenant with branding incomplete + an owner user. */
    private function freshTenantOwner(): array
    {
        $tenant = Tenant::factory()->create([
            'primary_color' => '#6366f1',
            'logo' => null,
            'settings' => [],
        ]);
        $owner = User::factory()->tenantOwner()->create(['tenant_id' => $tenant->id]);

        return [$tenant, $owner];
    }

    public function test_fresh_owner_is_redirected_to_wizard_once(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();

        // First hit on the admin area is redirected to the wizard.
        $this->actingAs($owner)->get('/admin')->assertRedirect('/admin/onboarding');

        // Viewing the wizard marks the tenant "seen".
        $this->actingAs($owner)->get('/admin/onboarding')->assertOk();

        // Subsequent admin hits are no longer redirected.
        $this->actingAs($owner)->get('/admin')->assertOk();
    }

    public function test_store_venue_creates_record_and_advances(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();

        $this->actingAs($owner)->post('/admin/onboarding/venue', [
            'name' => 'Grand Hall',
            'capacity_min' => 10,
            'capacity_max' => 200,
            'base_price' => 50000,
        ])->assertRedirect('/admin/onboarding');

        $this->assertDatabaseHas('venues', ['tenant_id' => $tenant->id, 'name' => 'Grand Hall']);
    }

    public function test_store_package_creates_record(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();

        $this->actingAs($owner)->post('/admin/onboarding/package', [
            'name' => 'Gold Wedding',
            'base_price' => 250000,
        ])->assertRedirect('/admin/onboarding');

        $this->assertDatabaseHas('packages', ['tenant_id' => $tenant->id, 'name' => 'Gold Wedding']);
    }

    public function test_branding_persists_and_completes_step(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();

        $this->actingAs($owner)->post('/admin/onboarding/branding', [
            'name' => 'Acme Events',
            'company_name' => 'Acme Events Co',
            'primary_color' => '#ff0066',
        ])->assertRedirect('/admin/onboarding');

        $tenant->refresh();
        $this->assertSame('#ff0066', $tenant->primary_color);
        $this->assertSame('Acme Events Co', $tenant->getSetting('branding')['company_name']);
    }

    public function test_invite_creates_login_user_with_temp_password_shown_once(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();

        $response = $this->actingAs($owner)->post('/admin/onboarding/invite', [
            'name' => 'New Manager',
            'email' => 'manager@acme.test',
            'role' => 'manager',
        ])->assertRedirect('/admin/onboarding');

        $response->assertSessionHas('invited_password');
        $password = session('invited_password');

        $invited = User::where('email', 'manager@acme.test')->first();
        $this->assertNotNull($invited);
        $this->assertSame($tenant->id, $invited->tenant_id);
        $this->assertSame('manager', $invited->role);
        $this->assertTrue($invited->hasRole('manager'));
        $this->assertTrue(Hash::check($password, $invited->password));
    }

    public function test_finish_dismisses_and_stops_redirect(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();

        $this->actingAs($owner)->post('/admin/onboarding/finish')->assertRedirect('/admin');

        // No more forced redirect.
        $this->actingAs($owner)->get('/admin')->assertOk();
        $this->assertTrue($tenant->fresh()->getSetting('onboarding')['dismissed']);
    }

    public function test_completed_tenant_is_not_redirected(): void
    {
        [$tenant, $owner] = $this->freshTenantOwner();
        $tenant->setSetting('branding', ['company_name' => 'Done Co']);
        Venue::factory()->create(['tenant_id' => $tenant->id]);
        Package::factory()->create(['tenant_id' => $tenant->id]);
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'staff']);

        $this->actingAs($owner)->get('/admin')->assertOk();
    }

    public function test_client_cannot_access_onboarding(): void
    {
        $tenant = Tenant::factory()->create();
        $client = User::factory()->client()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($client)->get('/admin/onboarding')->assertForbidden();
    }

    public function test_staff_cannot_access_onboarding(): void
    {
        $tenant = Tenant::factory()->create();
        $staff = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'staff']);

        $this->actingAs($staff)->get('/admin/onboarding')->assertForbidden();
    }
}
