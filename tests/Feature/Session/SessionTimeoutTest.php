<?php

namespace Tests\Feature\Session;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        // Skip the first-run wizard so /admin renders the dashboard for these tests.
        $this->tenant->setSetting('onboarding', ['seen' => true, 'dismissed' => true]);
        $this->admin = User::factory()->state(['role' => 'admin'])->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_idle_timeout_expires_the_session(): void
    {
        $this->actingAs($this->admin)
            ->withSession([
                'auth.login_at' => time() - 120,
                'auth.last_activity_at' => time() - 7200, // 2h idle > 60m
            ])
            ->get('/admin')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_absolute_timeout_expires_the_session(): void
    {
        $this->actingAs($this->admin)
            ->withSession([
                'auth.login_at' => time() - 50000, // > 12h
                'auth.last_activity_at' => time(),
            ])
            ->get('/admin')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_session_within_limits_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->withSession([
                'auth.login_at' => time() - 60,
                'auth.last_activity_at' => time() - 60,
            ])
            ->get('/admin')
            ->assertOk();
    }

    public function test_session_hardening_config(): void
    {
        $this->assertTrue(config('session.encrypt'));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertSame(3600, config('session.idle_timeout'));
        $this->assertSame(43200, config('session.absolute_timeout'));
    }
}
