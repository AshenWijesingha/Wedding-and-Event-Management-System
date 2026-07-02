<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_clients_index_renders(): void
    {
        Client::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->get('/admin/clients')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Clients/Index')->has('clients.data', 3));
    }

    public function test_bookings_create_renders(): void
    {
        Venue::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->get('/admin/bookings/create')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Bookings/Create')->has('venues')->has('clients'));
    }

    public function test_quotations_create_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/quotations/create')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Quotations/Create'));
    }

    public function test_quotation_show_renders(): void
    {
        $quotation = Quotation::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->get("/admin/quotations/{$quotation->id}")
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Quotations/Show'));
    }

    public function test_admin_can_store_booking(): void
    {
        $venue = Venue::factory()->create(['tenant_id' => $this->tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin)->post('/admin/bookings', [
            'client_id' => $client->id,
            'venue_id' => $venue->id,
            'event_type' => 'wedding',
            'event_date' => now()->addMonth()->toDateString(),
            'guest_count' => 120,
            'total_amount' => 15000,
            'paid_amount' => 5000,
            'status' => 'tentative',
        ]);

        $response->assertRedirect('/admin/bookings');
        $this->assertDatabaseHas('bookings', [
            'client_id' => $client->id,
            'balance_amount' => 10000,
        ]);
    }

    public function test_admin_can_store_quotation(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin)->post('/admin/quotations', [
            'client_id' => $client->id,
            'items' => [
                ['name' => 'Venue Hire', 'quantity' => 1, 'unit_price' => 6000],
                ['name' => 'Catering', 'quantity' => 100, 'unit_price' => 75],
            ],
            'discount_amount' => 500,
            'tax_rate' => 10,
        ]);

        $response->assertRedirect('/admin/quotations');
        // subtotal 13500, -500 discount = 13000, +10% tax = 14300
        $this->assertDatabaseHas('quotations', [
            'client_id' => $client->id,
            'total_amount' => 14300,
            'status' => 'draft',
        ]);
    }
}
