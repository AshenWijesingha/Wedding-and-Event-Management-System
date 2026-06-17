<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function makeBooking(array $attrs = []): Booking
    {
        $tid = $this->admin->tenant_id;

        return Booking::factory()->create(array_merge([
            'tenant_id'  => $tid,
            'venue_id'   => Venue::factory()->create(['tenant_id' => $tid])->id,
            'client_id'  => Client::factory()->create(['tenant_id' => $tid])->id,
            'event_date' => now()->addDays(10)->toDateString(),
            'status'     => 'confirmed',
        ], $attrs));
    }

    public function test_calendar_page_renders_with_events(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin)
            ->get('/admin/calendar')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p
                ->component('Calendar/Index')
                ->has('events', 1)
                ->where('events.0.id', $booking->id)
                ->where('events.0.status', 'confirmed')
                ->has('events.0.title')
                ->has('events.0.start')
                ->has('events.0.url')
            );
    }

    public function test_calendar_excludes_other_tenant_bookings(): void
    {
        $mine = $this->makeBooking();
        Booking::factory()->create(); // its own fresh tenant

        $this->actingAs($this->admin)
            ->get('/admin/calendar')
            ->assertInertia(fn ($p) => $p
                ->has('events', 1)
                ->where('events.0.id', $mine->id)
            );
    }

    public function test_user_without_bookings_view_is_forbidden(): void
    {
        $role = Role::firstOrCreate(['name' => 'no_bookings', 'guard_name' => 'web']);
        $user = User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'role' => 'staff']);
        $user->syncRoles([$role]);

        $this->actingAs($user)
            ->get('/admin/calendar')
            ->assertForbidden();
    }
}
