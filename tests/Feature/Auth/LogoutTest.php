<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Logout is always triggered from the Inertia SPA (router.post('/logout')). The
 * landing page ('/') is a plain Blade page, NOT an Inertia page, so a plain 302
 * redirect makes the Inertia client render the HTML response inside its error
 * modal (a "floating window"). The controller must instead emit an
 * Inertia-location response (409 + X-Inertia-Location) so the client performs a
 * real full-page visit to the home page.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        Tenant::factory()->create()->makeCurrent();

        return User::factory()->create();
    }

    public function test_inertia_logout_returns_a_full_page_location_visit_to_home(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post('/logout');

        // Inertia signals a full-page browser visit with 409 + X-Inertia-Location.
        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', '/');

        $this->assertGuest();
    }

    public function test_plain_logout_still_redirects_to_home(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
