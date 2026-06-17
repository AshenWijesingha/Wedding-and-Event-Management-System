<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function tid(): int
    {
        return $this->admin->tenant_id;
    }

    public function test_admin_can_view_create_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/clients/create')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Clients/Create'));
    }

    public function test_admin_can_store_client(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/clients', [
            'first_name' => 'Nora',
            'last_name'  => 'Perera',
            'email'      => 'nora@example.lk',
            'phone'      => '0771234567',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', [
            'email'     => 'nora@example.lk',
            'tenant_id' => $this->tid(),
        ]);
    }

    public function test_store_requires_first_and_last_name(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/clients', ['email' => 'x@example.lk'])
            ->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_email_must_be_unique_per_tenant(): void
    {
        Client::factory()->create(['tenant_id' => $this->tid(), 'email' => 'dup@example.lk']);

        $this->actingAs($this->admin)
            ->post('/admin/clients', [
                'first_name' => 'A', 'last_name' => 'B', 'email' => 'dup@example.lk',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_view_360_profile(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tid()]);

        $this->actingAs($this->admin)
            ->get("/admin/clients/{$client->id}")
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Clients/Show')
                ->has('client')
                ->has('bookings')
                ->has('inquiries')
                ->has('quotations')
                ->has('payments')
                ->has('stats'));
    }

    public function test_admin_can_update_client(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tid(), 'first_name' => 'Old']);

        $this->actingAs($this->admin)
            ->put("/admin/clients/{$client->id}", [
                'first_name' => 'New',
                'last_name'  => $client->last_name,
                'email'      => $client->email,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'first_name' => 'New']);
    }

    public function test_update_allows_keeping_same_email(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tid(), 'email' => 'keep@example.lk']);

        $this->actingAs($this->admin)
            ->put("/admin/clients/{$client->id}", [
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => 'keep@example.lk',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_admin_can_delete_client_without_relations(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tid()]);

        $this->actingAs($this->admin)
            ->delete("/admin/clients/{$client->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_delete_blocked_when_client_has_bookings(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tid()]);
        Booking::factory()->create(['tenant_id' => $this->tid(), 'client_id' => $client->id]);

        $this->actingAs($this->admin)
            ->delete("/admin/clients/{$client->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_staff_cannot_create_client(): void
    {
        $staff = User::factory()->create(['tenant_id' => $this->tid()]); // default role: staff (view only)

        $this->actingAs($staff)
            ->post('/admin/clients', ['first_name' => 'X', 'last_name' => 'Y'])
            ->assertForbidden();
    }

    public function test_manager_cannot_delete_client(): void
    {
        $manager = User::factory()->manager()->create(['tenant_id' => $this->tid()]);
        $client = Client::factory()->create(['tenant_id' => $this->tid()]);

        $this->actingAs($manager)
            ->delete("/admin/clients/{$client->id}")
            ->assertForbidden();
    }

    public function test_cannot_access_other_tenant_client(): void
    {
        $other = Tenant::factory()->create();
        $foreign = Client::factory()->create(['tenant_id' => $other->id]);

        $this->actingAs($this->admin)
            ->get("/admin/clients/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_access_clients(): void
    {
        $this->get('/admin/clients')->assertRedirect('/login');
    }
}
