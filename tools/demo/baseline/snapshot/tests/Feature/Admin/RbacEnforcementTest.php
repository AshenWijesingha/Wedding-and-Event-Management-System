<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Verifies the role -> capability matrix is enforced server-side: section access,
 * sensitive mutations, login routing, and area isolation (admin vs portal).
 */
class RbacEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    private function user(string $role): User
    {
        return User::factory()->state(['role' => $role])->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_staff_can_view_but_not_mutate_venues(): void
    {
        $staff = $this->user('staff');

        $this->actingAs($staff)->get('/admin/venues')->assertOk();
        $this->actingAs($staff)->get('/admin/venues/create')->assertForbidden();
        $this->actingAs($staff)->post('/admin/venues', [])->assertForbidden();
    }

    public function test_staff_cannot_reach_sections_without_permission(): void
    {
        $staff = $this->user('staff');

        // staff lacks reports.view, settings.view, users.view, vendors.view, staff.view
        $this->actingAs($staff)->get('/admin/reports')->assertForbidden();
        $this->actingAs($staff)->get('/admin/settings')->assertForbidden();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->get('/admin/vendors')->assertForbidden();
    }

    public function test_manager_can_view_reports_but_not_export(): void
    {
        $manager = $this->user('manager');

        $this->actingAs($manager)->get('/admin/reports')->assertOk();
        $this->actingAs($manager)->get('/admin/reports/revenue/export')->assertForbidden();
        // manager has no user management
        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_view_settings_but_not_edit(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->actingAs($admin)->post('/admin/settings/general', [])->assertForbidden();
        $this->actingAs($admin)->post('/admin/themes/activate', [])->assertForbidden();
    }

    public function test_tenant_owner_can_edit_settings(): void
    {
        $owner = $this->user('tenant_owner');

        $this->actingAs($owner)->get('/admin/settings')->assertOk();
    }

    public function test_client_is_blocked_from_admin_area(): void
    {
        $client = $this->user('client');

        $this->actingAs($client)->get('/admin')->assertForbidden();
        $this->actingAs($client)->get('/admin/bookings')->assertForbidden();
    }

    public function test_non_client_is_blocked_from_portal(): void
    {
        $staff = $this->user('staff');

        $this->actingAs($staff)->get('/portal')->assertForbidden();
    }

    public function test_login_redirects_client_to_portal_and_staff_to_admin(): void
    {
        $client = User::factory()
            ->state(['role' => 'client', 'password' => Hash::make('secret123')])
            ->create(['tenant_id' => $this->tenant->id]);
        $staff = User::factory()
            ->state(['role' => 'staff', 'password' => Hash::make('secret123')])
            ->create(['tenant_id' => $this->tenant->id]);

        $this->post('/login', ['email' => $client->email, 'password' => 'secret123'])
            ->assertRedirect(route('portal.dashboard'));

        $this->post('/logout');

        $this->post('/login', ['email' => $staff->email, 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Booking mutations are gated by the bookings.* permission middleware, which
     * super_admin and tenant_owner both hold — they must not be blocked by a
     * narrower in-controller role check.
     */
    public function test_privileged_roles_can_confirm_a_tentative_booking(): void
    {
        $venue = Venue::factory()->create(['tenant_id' => $this->tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        foreach (['super_admin', 'tenant_owner', 'admin', 'manager'] as $role) {
            $booking = Booking::factory()->create([
                'tenant_id' => $this->tenant->id,
                'venue_id' => $venue->id,
                'client_id' => $client->id,
                'status' => 'tentative',
            ]);

            $this->actingAs($this->user($role))
                ->post("/admin/bookings/{$booking->id}/confirm")
                ->assertRedirect();

            $this->assertSame('confirmed', $booking->fresh()->status, "{$role} should be able to confirm a booking");
        }
    }
}
