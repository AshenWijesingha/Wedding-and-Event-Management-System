<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provisions an isolated, throwaway demo tenant for the public sandbox: a fresh
 * tenant + a tenant_owner login + realistic seeded data, all scoped by
 * BelongsToTenant. Reaped after expiry by the demo:reap command.
 */
class DemoTenantProvisioner
{
    /**
     * @return array{tenant: Tenant, user: User}
     */
    public function provision(): array
    {
        return DB::transaction(function () {
            $lifetime = (int) config('eventpro.demo.lifetime_minutes', 60);

            $tenant = Tenant::create([
                'name'            => 'Demo Workspace',
                'slug'            => 'demo-'.Str::lower(Str::random(8)),
                'email'           => 'demo@demo.local',
                'phone'           => '+94 11 000 0000',
                'primary_color'   => '#7c3aed',
                'status'          => 'active',
                'is_demo'         => true,
                'demo_expires_at' => now()->addMinutes($lifetime),
                // Skip the first-run wizard; tours still auto-run for the showcase.
                'settings'        => ['onboarding' => ['seen' => true, 'dismissed' => true]],
            ]);

            $tenant->makeCurrent();

            $user = User::create([
                'tenant_id'         => $tenant->id,
                'name'              => 'Demo User',
                'email'             => 'demo-'.$tenant->id.'@demo.local',
                'password'          => Str::password(16), // hashed by the model cast
                'role'              => 'tenant_owner',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('tenant_owner');

            $this->seed($tenant->id);

            return ['tenant' => $tenant, 'user' => $user];
        });
    }

    /**
     * Seed a representative, fully-scoped dataset. Every factory receives an
     * explicit tenant_id (their defaults would otherwise spawn new tenants) and
     * foreign keys point at the records created here.
     */
    private function seed(int $tid): void
    {
        $venues   = Venue::factory(3)->create(['tenant_id' => $tid]);
        $packages = Package::factory(4)->create(['tenant_id' => $tid]);
        $clients  = Client::factory(6)->create(['tenant_id' => $tid]);
        $staff    = Staff::factory(3)->create(['tenant_id' => $tid]);
        Vendor::factory(4)->create(['tenant_id' => $tid]);

        Task::factory(5)->create([
            'tenant_id'   => $tid,
            'assigned_to' => $staff->random()->id,
        ]);

        $bookings = collect(range(1, 8))->map(fn () => Booking::factory()->create([
            'tenant_id'  => $tid,
            'venue_id'   => $venues->random()->id,
            'client_id'  => $clients->random()->id,
            'package_id' => $packages->random()->id,
        ]));

        $bookings->take(5)->each(fn (Booking $b) => Payment::factory()->completed()->create([
            'tenant_id'  => $tid,
            'booking_id' => $b->id,
            'client_id'  => $b->client_id,
        ]));

        Inquiry::factory(5)->create([
            'tenant_id'  => $tid,
            'client_id'  => $clients->random()->id,
            'venue_id'   => $venues->random()->id,
            'package_id' => $packages->random()->id,
        ]);

        Quotation::factory(4)->create([
            'tenant_id' => $tid,
            'client_id' => $clients->random()->id,
            'venue_id'  => $venues->random()->id,
        ]);
    }
}
