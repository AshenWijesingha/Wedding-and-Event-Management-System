<?php

namespace Tests\Unit;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovableTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_approve_reject_transitions_and_edit_flags_changes(): void
    {
        $u = User::factory()->create();
        $hotel = Hotel::factory()->draft()->create();

        $hotel->submit($u);
        $this->assertSame('pending', $hotel->approval_status);
        $this->assertSame($u->id, $hotel->submitted_by);
        $this->assertNotNull($hotel->submitted_at);

        $hotel->approve($u);
        $this->assertSame('approved', $hotel->approval_status);
        $this->assertFalse($hotel->changes_pending_review);

        // editing an approved record flags it for re-review but stays approved
        $hotel->update(['name' => 'Renamed Hotel']);
        $this->assertSame('approved', $hotel->fresh()->approval_status);
        $this->assertTrue($hotel->fresh()->changes_pending_review);

        $hotel->reject($u, 'Needs better photos');
        $this->assertSame('rejected', $hotel->approval_status);
        $this->assertSame('Needs better photos', $hotel->review_notes);
    }

    public function test_approved_scope_filters(): void
    {
        Hotel::factory()->approved()->create();
        Hotel::factory()->draft()->create();
        $this->assertSame(1, Hotel::approved()->count());
    }
}
