<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_bookings_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/bookings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Bookings/Index'));
    }

    public function test_admin_can_view_booking_detail(): void
    {
        $booking = Booking::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/admin/bookings/{$booking->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Bookings/Show'));
    }

    public function test_admin_can_confirm_booking(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->post("/admin/bookings/{$booking->id}/confirm");

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_can_cancel_booking(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin)
            ->post("/admin/bookings/{$booking->id}/cancel");

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_guest_cannot_view_bookings(): void
    {
        $response = $this->get('/admin/bookings');
        $response->assertRedirect('/login');
    }
}
