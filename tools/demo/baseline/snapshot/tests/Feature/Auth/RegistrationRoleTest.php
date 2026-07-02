<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Public signups attach to a tenant; ensure one exists.
        Tenant::factory()->create();
    }

    public function test_new_registration_receives_client_role_not_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user, 'User was not created.');
        $this->assertEquals('client', $user->role, 'New user should have role=client, not admin.');
        $this->assertTrue($user->hasRole('client'), 'New user should be assigned the client role.');
        $this->assertFalse($user->hasRole('admin'), 'New user must NOT be assigned the admin role.');
    }

    public function test_registered_user_is_redirected_and_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }
}
