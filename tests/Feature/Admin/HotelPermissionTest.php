<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HotelPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_permissions_exist_and_super_admin_has_approvals_review(): void
    {
        foreach (['hotels.view', 'hotels.create', 'hotels.submit', 'venues.submit', 'packages.submit', 'approvals.review'] as $p) {
            $this->assertTrue(
                Permission::where('name', $p)->exists(),
                "Permission [{$p}] should exist"
            );
        }

        $tenant = Tenant::factory()->create();
        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        $this->assertTrue($super->can('approvals.review'));
    }
}
