<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageSubmitTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->for($this->tenant)->create();
        $this->owner->assignRole('tenant_owner');
    }

    public function test_new_package_is_draft_and_can_be_submitted(): void
    {
        $hotel = Hotel::factory()->for($this->tenant)->approved()->create();

        $this->actingAs($this->owner)->post('/admin/packages', [
            'name'       => 'Wedding Gold Package',
            'base_price' => 50000,
            'min_guests' => 50,
            'max_guests' => 300,
            'hotel_id'   => $hotel->id,
        ])->assertRedirect();

        $package = Package::where('name', 'Wedding Gold Package')->first();
        $this->assertNotNull($package, 'Package should exist after creation');
        $this->assertSame('draft', $package->approval_status);
        $this->assertSame($hotel->id, $package->hotel_id);

        $this->actingAs($this->owner)
            ->post("/admin/packages/{$package->slug}/submit")
            ->assertRedirect();

        $this->assertSame('pending', $package->fresh()->approval_status);
    }

    public function test_cross_tenant_hotel_id_is_rejected(): void
    {
        $tenantB = Tenant::factory()->create();
        $hotelB  = Hotel::factory()->for($tenantB)->approved()->create();

        $this->actingAs($this->owner)->post('/admin/packages', [
            'name'       => 'Stolen Package',
            'base_price' => 50000,
            'min_guests' => 50,
            'max_guests' => 300,
            'hotel_id'   => $hotelB->id,
        ])->assertSessionHasErrors('hotel_id');

        $this->assertNull(Package::where('name', 'Stolen Package')->first());
    }
}
