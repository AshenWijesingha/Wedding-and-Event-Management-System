<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
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

    public function test_rejecting_changes_on_approved_item_keeps_it_live(): void
    {
        $hotel = Hotel::factory()->for($this->tenant)->approved()->create();
        $hotel->forceFill(['changes_pending_review' => true])->saveQuietly();

        $this->actingAs($this->super)
            ->post("/admin/approvals/hotel/{$hotel->id}/reject", ['notes' => 'Revert the changes'])
            ->assertRedirect();

        $fresh = $hotel->fresh();
        $this->assertSame('approved', $fresh->approval_status, 'Item must stay approved after rejecting live-edit changes');
        $this->assertFalse((bool) $fresh->changes_pending_review, 'changes_pending_review must be cleared');
        $this->assertSame('Revert the changes', $fresh->review_notes);
    }

    public function test_cannot_approve_a_draft(): void
    {
        $hotel = Hotel::factory()->for($this->tenant)->draft()->create();

        $this->actingAs($this->super)
            ->post("/admin/approvals/hotel/{$hotel->id}/approve")
            ->assertStatus(422);

        $this->assertSame('draft', $hotel->fresh()->approval_status);
    }

    public function test_approvals_index_lists_pending_items_across_all_types(): void
    {
        $user = User::factory()->for($this->tenant)->create();

        $hotel = Hotel::factory()->for($this->tenant)->pending()->create([
            'name' => 'Test Pending Hotel',
            'submitted_by' => $user->id,
        ]);

        $venue = Venue::factory()->for($this->tenant)->pending()->create([
            'name' => 'Test Pending Venue',
            'submitted_by' => $user->id,
        ]);

        $package = Package::factory()->for($this->tenant)->pending()->create([
            'name' => 'Test Pending Package',
            'submitted_by' => $user->id,
        ]);

        $this->actingAs($this->super)
            ->get('/admin/approvals')
            ->assertOk()
            ->assertSee($hotel->name)
            ->assertSee($venue->name)
            ->assertSee($package->name);
    }
}
