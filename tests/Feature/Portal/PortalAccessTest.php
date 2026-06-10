<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    public function test_registration_creates_a_linked_client_profile(): void
    {
        $this->post('/register', [
            'name' => 'Nimal Perera',
            'email' => 'nimal@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect(route('portal.dashboard'));

        $user = User::where('email', 'nimal@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('client'));

        $client = Client::where('user_id', $user->id)->first();
        $this->assertNotNull($client, 'Registration should create a linked client profile.');
        $this->assertSame('Nimal', $client->first_name);
        $this->assertSame('Perera', $client->last_name);
    }

    public function test_registered_client_can_open_portal_without_403(): void
    {
        $this->post('/register', [
            'name' => 'Dilani Silva',
            'email' => 'dilani@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ]);

        $user = User::where('email', 'dilani@example.com')->firstOrFail();

        $this->actingAs($user)->get('/portal')->assertOk();
    }

    public function test_portal_self_heals_client_without_profile(): void
    {
        // A client account that somehow has no Client profile must not dead-end on 403.
        $user = User::factory()->state(['role' => 'client'])->create(['tenant_id' => $this->tenant->id]);
        $this->assertNull($user->client);

        $this->actingAs($user)->get('/portal')->assertOk();
        $this->assertNotNull($user->fresh()->client);
    }
}
