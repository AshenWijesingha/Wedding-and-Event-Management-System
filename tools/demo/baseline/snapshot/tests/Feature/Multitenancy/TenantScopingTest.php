<?php

namespace Tests\Feature\Multitenancy;

use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable column-based scoping for these tests
        config(['eventpro.tenancy_mode' => 'column']);

        $this->tenantA = Tenant::create([
            'name' => 'Grand Palace',
            'slug' => 'grand-palace',
            'email' => 'admin@grandpalace.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Blue Lagoon',
            'slug' => 'blue-lagoon',
            'email' => 'admin@bluelagoon.test',
            'status' => 'active',
        ]);
    }

    public function test_venues_are_scoped_to_current_tenant(): void
    {
        $this->tenantA->makeCurrent();
        Venue::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Palace Ballroom',
            'slug' => 'palace-ballroom',
            'capacity_min' => 50,
            'capacity_max' => 300,
            'base_price' => 5000,
        ]);

        $this->tenantB->makeCurrent();
        Venue::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Lagoon Hall',
            'slug' => 'lagoon-hall',
            'capacity_min' => 20,
            'capacity_max' => 100,
            'base_price' => 2000,
        ]);

        // Tenant B's context: should only see Lagoon Hall
        $this->tenantB->makeCurrent();
        $venues = Venue::all();
        $this->assertCount(1, $venues);
        $this->assertEquals('Lagoon Hall', $venues->first()->name);

        // Tenant A's context: should only see Palace Ballroom
        $this->tenantA->makeCurrent();
        $venues = Venue::all();
        $this->assertCount(1, $venues);
        $this->assertEquals('Palace Ballroom', $venues->first()->name);
    }

    public function test_venue_is_auto_assigned_to_current_tenant(): void
    {
        $this->tenantA->makeCurrent();

        $venue = Venue::create([
            'name' => 'Auto Assigned Hall',
            'slug' => 'auto-assigned',
            'capacity_min' => 10,
            'capacity_max' => 50,
            'base_price' => 1000,
        ]);

        $this->assertEquals($this->tenantA->id, $venue->tenant_id);
    }

    public function test_tenant_settings_get_and_set(): void
    {
        $this->tenantA->setSetting('general.timezone', 'Asia/Colombo');

        $this->assertEquals('Asia/Colombo', $this->tenantA->fresh()->getSetting('general.timezone'));
    }

    public function test_tenant_is_on_trial(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trial Venue',
            'slug' => 'trial-venue',
            'email' => 'trial@test.com',
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->assertTrue($tenant->isOnTrial());
        $this->assertTrue($tenant->isActive());
    }

    public function test_suspended_tenant_is_not_active(): void
    {
        $tenant = Tenant::create([
            'name' => 'Suspended Venue',
            'slug' => 'suspended-venue',
            'email' => 'suspended@test.com',
            'status' => 'suspended',
        ]);

        $this->assertFalse($tenant->isActive());
    }
}
