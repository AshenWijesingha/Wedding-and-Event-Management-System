<?php

namespace Tests\Feature\Api\Client;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUserA;
    private User $clientUserB;
    private Client $clientA;
    private Quotation $quotationA;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $venue  = Venue::factory()->create(['tenant_id' => $tenant->id]);

        $this->clientUserA = User::factory()->client()->create(['tenant_id' => $tenant->id]);
        $this->clientUserB = User::factory()->client()->create(['tenant_id' => $tenant->id]);

        $this->clientA = Client::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $this->clientUserA->id]);
        $clientB       = Client::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $this->clientUserB->id]);

        $this->quotationA = Quotation::factory()->sent()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $this->clientA->id,
            'venue_id'  => $venue->id,
        ]);
    }

    public function test_client_can_view_their_own_quotation(): void
    {
        $response = $this->actingAs($this->clientUserA, 'sanctum')
            ->getJson("/api/v1/client/quotations/{$this->quotationA->id}");

        $response->assertOk();
    }

    public function test_client_cannot_view_another_clients_quotation(): void
    {
        $response = $this->actingAs($this->clientUserB, 'sanctum')
            ->getJson("/api/v1/client/quotations/{$this->quotationA->id}");

        $response->assertForbidden();
    }

    public function test_client_can_accept_their_own_sent_quotation(): void
    {
        $response = $this->actingAs($this->clientUserA, 'sanctum')
            ->postJson("/api/v1/client/quotations/{$this->quotationA->id}/accept");

        $response->assertOk();
        $this->assertEquals('accepted', $this->quotationA->fresh()->status);
        $this->assertNotNull($this->quotationA->fresh()->accepted_at);
    }

    public function test_client_cannot_accept_another_clients_quotation(): void
    {
        $response = $this->actingAs($this->clientUserB, 'sanctum')
            ->postJson("/api/v1/client/quotations/{$this->quotationA->id}/accept");

        $response->assertForbidden();
        $this->assertNotEquals('accepted', $this->quotationA->fresh()->status);
    }

    public function test_accepting_expired_quotation_returns_422(): void
    {
        $this->quotationA->update(['status' => 'expired']);

        $response = $this->actingAs($this->clientUserA, 'sanctum')
            ->postJson("/api/v1/client/quotations/{$this->quotationA->id}/accept");

        $response->assertUnprocessable();
    }
}
