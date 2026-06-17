<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end smoke of every flow shown in the demonstration guide, against the
 * REAL seeded demo data (DatabaseSeeder). If this is green, the live demo works.
 */
class DemoReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    private function login(string $email): User
    {
        // No tenant is current in the test harness, so bypass the BelongsToTenant
        // global scope to find the seeded user (real login resolves tenant from auth).
        $user = User::withoutGlobalScopes()->where('email', $email)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    /** @dataProvider publicPages */
    public function test_public_pages_load(string $path): void
    {
        $this->get($path)->assertSuccessful();
    }

    public static function publicPages(): array
    {
        return [['/'], ['/venues'], ['/packages'], ['/contact'], ['/links']];
    }

    public function test_public_inquiry_can_be_submitted(): void
    {
        $this->post('/inquiry', [
            'name'    => 'Prospective Client',
            'email'   => 'prospect@example.test',
            'message' => 'I would like to inquire about a wedding package for next year.',
        ])->assertRedirect();

        $this->assertDatabaseHas('inquiries', ['source' => 'website']);
    }

    public function test_new_user_can_register_and_reach_the_portal(): void
    {
        $this->get('/register')->assertSuccessful();

        $this->post('/register', [
            'name'                  => 'New Demo Client',
            'email'                 => 'new.client@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new.client@example.test', 'role' => 'client']);
        // The portal needs a linked Client profile — registration must create one.
        $this->assertDatabaseHas('clients', ['email' => 'new.client@example.test']);

        $this->get('/portal')->assertSuccessful();
        $this->get('/portal/bookings')->assertSuccessful();
        $this->get('/portal/quotations')->assertSuccessful();
        $this->get('/portal/payments')->assertSuccessful();
    }

    public function test_super_admin_pages_load(): void
    {
        $this->login('admin@eventpro.io');

        foreach (['/admin', '/admin/tenants', '/admin/plans', '/admin/audit-log', '/admin/platform-settings', '/admin/users'] as $path) {
            $this->get($path)->assertSuccessful();
        }
    }

    /** @dataProvider adminSections */
    public function test_tenant_admin_sections_load(string $path): void
    {
        $this->login('admin@demo.eventpro.test');
        $this->get($path)->assertSuccessful();
    }

    public static function adminSections(): array
    {
        return array_map(fn ($p) => [$p], [
            '/admin/bookings', '/admin/calendar', '/admin/clients', '/admin/inquiries',
            '/admin/quotations', '/admin/venues', '/admin/packages', '/admin/vendors',
            '/admin/staff', '/admin/tasks', '/admin/payments', '/admin/reports',
            '/admin/settings', '/admin/profile',
        ]);
    }
}
