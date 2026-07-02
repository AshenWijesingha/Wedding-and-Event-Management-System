<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Converting an inquiry into a booking carries every known detail forward, so
 * staff only fill the gaps (times, deposit). The create form receives a prefill
 * payload derived from the inquiry.
 */
class BookingPrefillTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->tenant = $this->admin->tenant;
    }

    public function test_booking_create_prefills_from_inquiry(): void
    {
        $venue = Venue::factory()->create(['tenant_id' => $this->tenant->id, 'base_price' => 800000, 'status' => 'active']);
        $package = Package::factory()->create(['tenant_id' => $this->tenant->id, 'base_price' => 200000, 'status' => 'active']);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $inquiry = Inquiry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'event_type' => 'wedding',
            'preferred_date' => '2027-03-15',
            'guest_count' => 220,
            'message' => 'Looking for a grand wedding.',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/bookings/create?inquiry_id={$inquiry->id}")
            ->assertInertia(fn ($page) => $page
                ->component('Bookings/Create')
                ->where('prefill.client_id', $client->id)
                ->where('prefill.venue_id', $venue->id)
                ->where('prefill.package_id', $package->id)
                ->where('prefill.event_type', 'wedding')
                ->where('prefill.event_date', '2027-03-15')
                ->where('prefill.guest_count', 220)
                ->where('prefill.notes', 'Looking for a grand wedding.')
                // venue base_price + package base_price (JSON-encoded as a number).
                ->where('prefill.total_amount', 1000000));
    }

    public function test_booking_create_without_inquiry_has_empty_prefill(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/bookings/create')
            ->assertInertia(fn ($page) => $page
                ->component('Bookings/Create')
                ->where('prefill', []));
    }
}
