<?php

namespace Tests\Feature\Demo;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Venue;
use App\Services\DemoTenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoSandboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_creates_isolated_demo_tenant_with_data(): void
    {
        ['tenant' => $tenant, 'user' => $user] = app(DemoTenantProvisioner::class)->provision();

        $this->assertTrue($tenant->is_demo);
        $this->assertTrue($tenant->demo_expires_at->isFuture());
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('tenant_owner', $user->role);
        $this->assertTrue($user->hasRole('tenant_owner'));

        $this->assertSame(3, Venue::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(6, Booking::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        // Onboarding wizard skipped.
        $this->assertTrue($tenant->getSetting('onboarding')['dismissed']);
    }

    public function test_provision_seeds_the_hero_story(): void
    {
        $tenant = app(DemoTenantProvisioner::class)->provision()['tenant'];
        $tid = $tenant->id;

        $hero = Client::withoutGlobalScopes()->where('tenant_id', $tid)
            ->where('first_name', 'Priya & Arjun')->first();
        $this->assertNotNull($hero, 'Hero client seeded');

        $booking = Booking::withoutGlobalScopes()->where('tenant_id', $tid)
            ->where('client_id', $hero->id)->where('status', 'confirmed')->first();
        $this->assertNotNull($booking, 'Hero booking confirmed');
        $this->assertEquals(500000, $booking->paid_amount);
        $this->assertEquals(1350000, $booking->balance_amount);

        $this->assertSame(1, Payment::withoutGlobalScopes()->where('tenant_id', $tid)
            ->where('booking_id', $booking->id)->where('status', 'completed')->where('amount', 500000)->count());
    }

    public function test_two_demos_are_isolated(): void
    {
        $a = app(DemoTenantProvisioner::class)->provision()['tenant'];
        $b = app(DemoTenantProvisioner::class)->provision()['tenant'];

        $this->assertNotSame($a->id, $b->id);
        $this->assertSame(6, Booking::withoutGlobalScopes()->where('tenant_id', $a->id)->count());
        $this->assertSame(0, Booking::withoutGlobalScopes()
            ->where('tenant_id', $a->id)->where('venue_id', function ($q) use ($b) {
                $q->select('id')->from('venues')->where('tenant_id', $b->id)->limit(1);
            })->count());
    }

    public function test_start_provisions_and_logs_in_when_enabled(): void
    {
        config(['eventpro.demo.enabled' => true]);

        $this->post('/demo/start')->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertTrue(Tenant::where('is_demo', true)->exists());
    }

    public function test_start_is_404_when_disabled(): void
    {
        config(['eventpro.demo.enabled' => false]);

        $this->post('/demo/start')->assertNotFound();
        $this->assertFalse(Tenant::where('is_demo', true)->exists());
    }

    public function test_start_refuses_at_capacity(): void
    {
        config(['eventpro.demo.enabled' => true, 'eventpro.demo.max_live' => 0]);

        $this->post('/demo/start')->assertRedirect('/');
        $this->assertFalse(Tenant::where('is_demo', true)->exists());
    }

    public function test_reaper_deletes_only_expired_demos(): void
    {
        $expired = Tenant::factory()->create(['is_demo' => true, 'demo_expires_at' => now()->subHour()]);
        $expiredVenue = Venue::factory()->create(['tenant_id' => $expired->id]);

        $live = Tenant::factory()->create(['is_demo' => true, 'demo_expires_at' => now()->addHour()]);
        $normal = Tenant::factory()->create(['is_demo' => false]);
        $normalVenue = Venue::factory()->create(['tenant_id' => $normal->id]);

        Artisan::call('demo:reap');

        $this->assertNull(Tenant::find($expired->id));
        $this->assertNull(Venue::withoutGlobalScopes()->find($expiredVenue->id));
        $this->assertNotNull(Tenant::find($live->id));
        $this->assertNotNull(Tenant::find($normal->id));
        $this->assertNotNull(Venue::withoutGlobalScopes()->find($normalVenue->id));
    }

    public function test_demo_banner_state_is_shared(): void
    {
        config(['eventpro.demo.enabled' => true]);
        $user = app(DemoTenantProvisioner::class)->provision()['user'];

        $this->actingAs($user)
            ->get('/admin')
            ->assertInertia(fn ($p) => $p->where('demo.is_demo', true));
    }
}
