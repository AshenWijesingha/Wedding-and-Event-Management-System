<?php

namespace Tests\Feature\Api\Client;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUserA;

    private User $clientUserB;

    private Client $clientA;

    private Client $clientB;

    private Booking $bookingA;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $venue = Venue::factory()->create(['tenant_id' => $tenant->id]);

        $this->clientUserA = User::factory()->client()->create(['tenant_id' => $tenant->id]);
        $this->clientUserB = User::factory()->client()->create(['tenant_id' => $tenant->id]);

        $this->clientA = Client::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $this->clientUserA->id]);
        $this->clientB = Client::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $this->clientUserB->id]);

        $this->bookingA = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $this->clientA->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_client_can_view_their_own_booking(): void
    {
        $response = $this->actingAs($this->clientUserA, 'sanctum')
            ->getJson("/api/v1/client/bookings/{$this->bookingA->id}");

        $response->assertOk();
    }

    public function test_client_cannot_view_another_clients_booking(): void
    {
        $response = $this->actingAs($this->clientUserB, 'sanctum')
            ->getJson("/api/v1/client/bookings/{$this->bookingA->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_booking(): void
    {
        $response = $this->getJson("/api/v1/client/bookings/{$this->bookingA->id}");

        $response->assertUnauthorized();
    }
}
