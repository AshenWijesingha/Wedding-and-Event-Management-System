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
        $venues   = $this->seedVenues($tid);
        $packages = $this->seedPackages($tid);
        $clients  = $this->seedClients($tid);
        $staff    = $this->seedTeam($tid);

        $this->seedHeroStory($tid, $clients[0], $venues[0], $packages[2]);
        $this->seedSupporting($tid, $clients, $venues, $packages, $staff);
    }

    private function seedVenues(int $tid)
    {
        return collect([
            ['name' => 'Mahaweli Grand Ballroom', 'capacity_min' => 100, 'capacity_max' => 500, 'base_price' => 1500000],
            ['name' => 'Cinnamon Garden Lawns',   'capacity_min' => 50,  'capacity_max' => 250, 'base_price' => 850000],
            ['name' => 'Galle Face Terrace',      'capacity_min' => 30,  'capacity_max' => 150, 'base_price' => 650000],
        ])->map(fn ($v) => Venue::factory()->create($v + [
            'tenant_id' => $tid,
            'slug'      => Str::slug($v['name']).'-'.Str::lower(Str::random(4)),
        ]));
    }

    private function seedPackages(int $tid)
    {
        return collect([
            ['name' => 'Silver Collection',  'base_price' => 450000,  'min_guests' => 50,  'max_guests' => 150],
            ['name' => 'Gold Collection',    'base_price' => 850000,  'min_guests' => 100, 'max_guests' => 300],
            ['name' => 'Platinum Collection','base_price' => 1500000, 'min_guests' => 150, 'max_guests' => 500],
            ['name' => 'Bespoke Experience', 'base_price' => 2500000, 'min_guests' => 50,  'max_guests' => 500],
        ])->map(fn ($p) => Package::factory()->create($p + [
            'tenant_id' => $tid,
            'slug'      => Str::slug($p['name']).'-'.Str::lower(Str::random(4)),
        ]));
    }

    private function seedClients(int $tid)
    {
        return collect([
            ['first_name' => 'Priya & Arjun', 'last_name' => 'Fernando-Mendis', 'email' => 'priya.arjun@example.lk'],
            ['first_name' => 'Nimal',    'last_name' => 'Perera',        'email' => 'nimal.perera@example.lk'],
            ['first_name' => 'Sanduni',  'last_name' => 'Jayawardena',   'email' => 'sanduni.j@example.lk'],
            ['first_name' => 'Ruwan',    'last_name' => 'Silva',         'email' => 'ruwan.silva@example.lk'],
            ['first_name' => 'Aisha',    'last_name' => 'Hussain',       'email' => 'aisha.hussain@example.lk'],
            ['first_name' => 'Kavya',    'last_name' => 'Rajapaksha',    'email' => 'kavya.r@example.lk'],
        ])->map(fn ($c) => Client::factory()->create($c + ['tenant_id' => $tid]));
    }

    private function seedTeam(int $tid)
    {
        $vendors = [
            ['name' => 'Lanka Lens Photography', 'category' => 'photographer'],
            ['name' => 'Spice Route Catering',   'category' => 'caterer'],
            ['name' => 'Orchid & Ivory Florals', 'category' => 'florist'],
            ['name' => 'Rhythm Nation DJs',      'category' => 'music'],
        ];
        foreach ($vendors as $v) {
            Vendor::factory()->create($v + [
                'tenant_id' => $tid,
                'slug'      => Str::slug($v['name']).'-'.Str::lower(Str::random(4)),
            ]);
        }

        return collect([
            ['first_name' => 'Dilani',   'last_name' => 'Wickramasinghe', 'role' => 'coordinator'],
            ['first_name' => 'Tharindu', 'last_name' => 'Bandara',        'role' => 'manager'],
            ['first_name' => 'Maya',     'last_name' => 'Gunasekara',     'role' => 'assistant'],
        ])->map(fn ($s) => Staff::factory()->create($s + ['tenant_id' => $tid]));
    }

    /**
     * The thread the sales runbook walks: inquiry → accepted quotation →
     * confirmed booking → completed deposit, with a real outstanding balance.
     */
    private function seedHeroStory(int $tid, $client, $venue, $package): void
    {
        $eventDate = now()->addDays(90)->toDateString();

        $booking = Booking::factory()->create([
            'tenant_id'      => $tid,
            'venue_id'       => $venue->id,
            'client_id'      => $client->id,
            'package_id'     => $package->id,
            'event_type'     => 'wedding',
            'event_date'     => $eventDate,
            'guest_count'    => 250,
            'total_amount'   => 1850000,
            'paid_amount'    => 500000,
            'balance_amount' => 1350000,
            'status'         => 'confirmed',
        ]);

        Payment::factory()->create([
            'tenant_id'        => $tid,
            'booking_id'       => $booking->id,
            'client_id'        => $client->id,
            'installment_name' => 'Deposit',
            'amount'           => 500000,
            'payment_method'   => 'bank_transfer',
            'status'           => 'completed',
            'payment_date'     => now()->subDays(14)->toDateString(),
        ]);

        Inquiry::factory()->create([
            'tenant_id'  => $tid,
            'client_id'  => $client->id,
            'venue_id'   => $venue->id,
            'package_id' => $package->id,
            'event_type' => 'wedding',
            'guest_count'=> 250,
            'status'     => 'proposal_sent',
        ]);

        Quotation::factory()->create([
            'tenant_id'       => $tid,
            'client_id'       => $client->id,
            'venue_id'        => $venue->id,
            'package_id'      => $package->id,
            'event_date'      => $eventDate,
            'guest_count'     => 250,
            'subtotal'        => 1750000,
            'discount_amount' => 0,
            'tax_amount'      => 100000,
            'total_amount'    => 1850000,
            'valid_until'     => now()->addDays(30)->toDateString(),
            'status'          => 'accepted',
        ]);
    }

    private function seedSupporting(int $tid, $clients, $venues, $packages, $staff): void
    {
        // A spread of bookings across statuses for a populated-but-clean app.
        $statuses = ['pending', 'tentative', 'confirmed', 'completed', 'confirmed'];
        foreach ($statuses as $i => $status) {
            $venue = $venues[$i % 3];
            $package = $packages[$i % 4];
            $client = $clients[$i + 1];
            $total = (float) $package->base_price + (float) $venue->base_price;
            $paid = in_array($status, ['confirmed', 'completed'], true) ? round($total * 0.4) : 0;

            $booking = Booking::factory()->create([
                'tenant_id'      => $tid,
                'venue_id'       => $venue->id,
                'client_id'      => $client->id,
                'package_id'     => $package->id,
                'event_type'     => 'wedding',
                'event_date'     => now()->addDays(30 + $i * 20)->toDateString(),
                'guest_count'    => 120 + $i * 20,
                'total_amount'   => $total,
                'paid_amount'    => $paid,
                'balance_amount' => $total - $paid,
                'status'         => $status,
            ]);

            if ($paid > 0) {
                Payment::factory()->create([
                    'tenant_id'        => $tid,
                    'booking_id'       => $booking->id,
                    'client_id'        => $client->id,
                    'installment_name' => 'Deposit',
                    'amount'           => $paid,
                    'payment_method'   => 'card',
                    'status'           => 'completed',
                    'payment_date'     => now()->subDays(7)->toDateString(),
                ]);
            }
        }

        // A couple more quotations + inquiries in varied states.
        Quotation::factory()->create(['tenant_id' => $tid, 'client_id' => $clients[1]->id, 'venue_id' => $venues[1]->id, 'package_id' => $packages[1]->id, 'status' => 'sent']);
        Quotation::factory()->create(['tenant_id' => $tid, 'client_id' => $clients[2]->id, 'venue_id' => $venues[2]->id, 'package_id' => $packages[0]->id, 'status' => 'draft']);
        Inquiry::factory()->create(['tenant_id' => $tid, 'client_id' => $clients[3]->id, 'venue_id' => $venues[0]->id, 'package_id' => $packages[1]->id, 'event_type' => 'corporate', 'status' => 'pending']);
        Inquiry::factory()->create(['tenant_id' => $tid, 'client_id' => $clients[4]->id, 'venue_id' => $venues[1]->id, 'package_id' => $packages[2]->id, 'event_type' => 'birthday', 'status' => 'contacted']);

        // Tasks tied to the wedding, assigned across the team.
        $tasks = [
            ['title' => 'Confirm floral arrangements with Orchid & Ivory', 'priority' => 'high'],
            ['title' => 'Finalize menu tasting with Spice Route', 'priority' => 'medium'],
            ['title' => 'Send balance payment reminder to the Fernando-Mendis wedding', 'priority' => 'high'],
            ['title' => 'Book photographer walkthrough', 'priority' => 'low'],
        ];
        foreach ($tasks as $i => $t) {
            Task::factory()->create($t + [
                'tenant_id'   => $tid,
                'assigned_to' => $staff[$i % 3]->id,
                'status'      => 'pending',
            ]);
        }
    }
}
