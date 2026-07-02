<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function makeBooking(array $attrs = []): Booking
    {
        $tid = $this->admin->tenant_id;

        return Booking::factory()->create(array_merge([
            'tenant_id' => $tid,
            'venue_id' => Venue::factory()->create(['tenant_id' => $tid])->id,
            'client_id' => Client::factory()->create(['tenant_id' => $tid])->id,
            'event_date' => now()->addDays(30)->toDateString(),
            'status' => 'tentative',
        ], $attrs));
    }

    private function payload(Booking $b, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $b->client_id,
            'venue_id' => $b->venue_id,
            'event_type' => 'wedding',
            'event_date' => $b->event_date->toDateString(),
            'guest_count' => 150,
            'total_amount' => 500000,
            'paid_amount' => 100000,
            'status' => 'confirmed',
        ], $overrides);
    }

    public function test_admin_can_view_edit_page(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin)
            ->get("/admin/bookings/{$booking->id}/edit")
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Bookings/Edit')->has('booking')->has('venues')->has('clients'));
    }

    public function test_admin_can_update_booking_and_balance_recomputed(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin)
            ->put("/admin/bookings/{$booking->id}", $this->payload($booking))
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'guest_count' => 150,
            'total_amount' => 500000,
            'paid_amount' => 100000,
            'balance_amount' => 400000,
            'status' => 'confirmed',
        ]);
    }

    public function test_saving_same_date_is_not_a_self_conflict(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin)
            ->put("/admin/bookings/{$booking->id}", $this->payload($booking))
            ->assertSessionHasNoErrors();
    }

    public function test_update_rejected_when_another_booking_holds_the_date(): void
    {
        $booking = $this->makeBooking();
        // A different booking on the same venue + a new target date.
        $targetDate = now()->addDays(60)->toDateString();
        Booking::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'venue_id' => $booking->venue_id,
            'event_date' => $targetDate,
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->put("/admin/bookings/{$booking->id}", $this->payload($booking, ['event_date' => $targetDate]))
            ->assertSessionHasErrors('event_date');
    }

    public function test_completed_booking_cannot_be_edited(): void
    {
        $booking = $this->makeBooking(['status' => 'completed']);

        $this->actingAs($this->admin)
            ->put("/admin/bookings/{$booking->id}", $this->payload($booking, ['guest_count' => 999]))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id, 'guest_count' => 999]);
    }

    public function test_staff_cannot_edit_booking(): void
    {
        $staff = User::factory()->create(['tenant_id' => $this->admin->tenant_id]); // staff role
        $booking = $this->makeBooking();

        $this->actingAs($staff)
            ->put("/admin/bookings/{$booking->id}", $this->payload($booking))
            ->assertForbidden();
    }

    public function test_cannot_edit_other_tenant_booking(): void
    {
        $foreign = Booking::factory()->create(); // its own fresh tenant

        $this->actingAs($this->admin)
            ->get("/admin/bookings/{$foreign->id}/edit")
            ->assertNotFound();
    }
}
