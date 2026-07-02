<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalReviewTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->super = User::factory()->create(['tenant_id' => null]);
        $this->super->assignRole('super_admin');
    }

    public function test_super_admin_approves_a_pending_hotel(): void
    {
        $hotel = Hotel::factory()->for($this->tenant)->pending()->create();

        $this->actingAs($this->super)
            ->post("/admin/approvals/hotel/{$hotel->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $hotel->fresh()->approval_status);
    }

    public function test_super_admin_rejects_with_notes(): void
    {
        $hotel = Hotel::factory()->for($this->tenant)->pending()->create();

        $this->actingAs($this->super)
            ->post("/admin/approvals/hotel/{$hotel->id}/reject", ['notes' => 'Add photos'])
            ->assertRedirect();

        $this->assertSame('rejected', $hotel->fresh()->approval_status);
        $this->assertSame('Add photos', $hotel->fresh()->review_notes);
    }

    public function test_non_super_admin_cannot_review(): void
    {
        $owner = User::factory()->for($this->tenant)->create();
        $owner->assignRole('tenant_owner');
        $hotel = Hotel::factory()->for($this->tenant)->pending()->create();

        $this->actingAs($owner)
            ->post("/admin/approvals/hotel/{$hotel->id}/approve")
            ->assertForbidden();
    }
}
