<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A large, fully-relational showcase dataset on its own tenant ("Showcase Events
 * Co.") so every admin page, report, and the availability calendar look full and
 * realistic for demos. 10+ of every entity, all scoped via BelongsToTenant.
 *
 * Independent of the fixed grand-vista DemoDataSeeder and the per-visitor
 * DemoTenantProvisioner — those are intentionally left untouched. Idempotent:
 * re-running migrate:fresh --seed or db:seed is safe.
 */
class ShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'showcase'],
            [
                'name'          => 'Showcase Events Co.',
                'email'         => 'hello@showcase.eventpro.test',
                'phone'         => '+94 11 234 5678',
                'primary_color' => '#7c3aed',
                'status'        => 'active',
                // Skip the first-run wizard; the app should look established.
                'settings'      => ['onboarding' => ['seen' => true, 'dismissed' => true]],
            ]
        );

        $tenant->makeCurrent();
        $tid = $tenant->id;

        $this->seedUsers($tid);

        // Idempotency guard: only build the relational dataset once per tenant.
        // (Queries are already tenant-scoped by the current tenant.)
        if (Booking::count() > 0) {
            return;
        }

        $venues   = $this->seedVenues($tid);
        $packages = $this->seedPackages($tid);
        $clients  = $this->seedClients($tid);
        $vendors  = $this->seedVendors($tid);
        $staff    = $this->seedStaff($tid);

        $inquiries  = $this->seedInquiries($tid, $clients, $venues, $packages);
        $quotations = $this->seedQuotations($tid, $clients, $venues, $packages, $inquiries);
        $bookings   = $this->seedBookings($tid, $clients, $venues, $packages);
        $this->seedPayments($tid, $bookings);
        $this->seedTasks($tid, $staff, $bookings);
    }

    private function seedUsers(int $tid): void
    {
        $users = [
            ['name' => 'Olivia Ranasinghe', 'email' => 'owner@showcase.eventpro.test',   'role' => 'tenant_owner'],
            ['name' => 'Dean Abeywardena',  'email' => 'admin@showcase.eventpro.test',   'role' => 'admin'],
            ['name' => 'Marcus Fonseka',    'email' => 'manager@showcase.eventpro.test', 'role' => 'manager'],
            ['name' => 'Hannah De Silva',   'email' => 'staff@showcase.eventpro.test',   'role' => 'staff'],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'tenant_id'         => $tid,
                    'name'              => $u['name'],
                    'password'          => Hash::make('password'),
                    'role'              => $u['role'],
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$u['role']]);
        }
    }

    private function seedVenues(int $tid)
    {
        $named = [
            ['name' => 'The Grand Pavilion',        'capacity_min' => 150, 'capacity_max' => 600, 'base_price' => 1800000],
            ['name' => 'Lakeside Crystal Hall',     'capacity_min' => 100, 'capacity_max' => 450, 'base_price' => 1400000],
            ['name' => 'Emerald Garden Terrace',    'capacity_min' => 40,  'capacity_max' => 200, 'base_price' => 750000],
            ['name' => 'Skyline Rooftop Lounge',    'capacity_min' => 50,  'capacity_max' => 180, 'base_price' => 950000],
            ['name' => 'Colonial Courtyard',        'capacity_min' => 30,  'capacity_max' => 150, 'base_price' => 600000],
            ['name' => 'Marina Bay Ballroom',       'capacity_min' => 120, 'capacity_max' => 500, 'base_price' => 1600000],
            ['name' => 'Heritage Manor Lawns',      'capacity_min' => 80,  'capacity_max' => 350, 'base_price' => 1100000],
            ['name' => 'Sapphire Banquet Suite',    'capacity_min' => 60,  'capacity_max' => 250, 'base_price' => 850000],
            ['name' => 'Palm Grove Beachfront',     'capacity_min' => 40,  'capacity_max' => 220, 'base_price' => 1250000],
            ['name' => 'Orchid Conference Center',  'capacity_min' => 100, 'capacity_max' => 400, 'base_price' => 700000],
            ['name' => 'Velvet Rose Hall',          'capacity_min' => 50,  'capacity_max' => 200, 'base_price' => 680000],
            ['name' => 'Highland Vista Retreat',    'capacity_min' => 30,  'capacity_max' => 120, 'base_price' => 900000],
        ];

        return collect($named)->map(fn ($v) => Venue::factory()->create($v + [
            'tenant_id' => $tid,
            'slug'      => Str::slug($v['name']).'-'.Str::lower(Str::random(4)),
        ]));
    }

    private function seedPackages(int $tid)
    {
        $named = [
            ['name' => 'Essential Wedding',   'base_price' => 380000,  'min_guests' => 50,  'max_guests' => 120],
            ['name' => 'Silver Celebration',  'base_price' => 550000,  'min_guests' => 80,  'max_guests' => 180],
            ['name' => 'Gold Signature',      'base_price' => 850000,  'min_guests' => 100, 'max_guests' => 300],
            ['name' => 'Platinum Luxe',       'base_price' => 1500000, 'min_guests' => 150, 'max_guests' => 500],
            ['name' => 'Diamond Royale',      'base_price' => 2400000, 'min_guests' => 200, 'max_guests' => 600],
            ['name' => 'Corporate Standard',  'base_price' => 320000,  'min_guests' => 40,  'max_guests' => 200],
            ['name' => 'Corporate Premium',   'base_price' => 680000,  'min_guests' => 80,  'max_guests' => 400],
            ['name' => 'Intimate Soirée',     'base_price' => 250000,  'min_guests' => 20,  'max_guests' => 80],
            ['name' => 'Birthday Bliss',      'base_price' => 180000,  'min_guests' => 20,  'max_guests' => 100],
            ['name' => 'Anniversary Elegance','base_price' => 420000,  'min_guests' => 40,  'max_guests' => 150],
            ['name' => 'Gala Grandeur',       'base_price' => 1900000, 'min_guests' => 150, 'max_guests' => 500],
            ['name' => 'Bespoke Atelier',     'base_price' => 3000000, 'min_guests' => 50,  'max_guests' => 600],
        ];

        return collect($named)->map(fn ($p) => Package::factory()->create($p + [
            'tenant_id' => $tid,
            'slug'      => Str::slug($p['name']).'-'.Str::lower(Str::random(4)),
        ]));
    }

    private function seedClients(int $tid)
    {
        $named = [
            ['first_name' => 'Amaya',    'last_name' => 'Wijeratne'],
            ['first_name' => 'Rohan',    'last_name' => 'Gunawardena'],
            ['first_name' => 'Shenali',  'last_name' => 'Karunaratne'],
            ['first_name' => 'Dinesh',   'last_name' => 'Wickramasekara'],
            ['first_name' => 'Tanya',    'last_name' => 'Mendis'],
            ['first_name' => 'Farhan',   'last_name' => 'Iqbal'],
            ['first_name' => 'Ishara',   'last_name' => 'Senanayake'],
            ['first_name' => 'Lakmal',   'last_name' => 'Dissanayake'],
            ['first_name' => 'Nethmi',   'last_name' => 'Abeysinghe'],
            ['first_name' => 'Chamath',  'last_name' => 'Liyanage'],
            ['first_name' => 'Devni',    'last_name' => 'Rathnayake'],
            ['first_name' => 'Yasiru',   'last_name' => 'Kodithuwakku'],
            ['first_name' => 'Hiruni',   'last_name' => 'Samaraweera'],
            ['first_name' => 'Pasan',    'last_name' => 'Weerasinghe'],
            ['first_name' => 'Oshadi',   'last_name' => 'Bandaranayake'],
        ];

        return collect($named)->map(fn ($c, $i) => Client::factory()->create($c + [
            'tenant_id' => $tid,
            'email'     => Str::lower($c['first_name'].'.'.$c['last_name']).$i.'@example.lk',
        ]));
    }

    private function seedVendors(int $tid)
    {
        $named = [
            ['name' => 'Lumière Photography',     'category' => 'photographer', 'status' => 'active'],
            ['name' => 'Royal Feast Catering',    'category' => 'caterer',      'status' => 'active'],
            ['name' => 'Petal & Stem Florals',    'category' => 'florist',      'status' => 'active'],
            ['name' => 'Beatbox Entertainment',   'category' => 'music',        'status' => 'active'],
            ['name' => 'Grand Decor Studio',      'category' => 'decor',        'status' => 'active'],
            ['name' => 'Elite Limousine Service', 'category' => 'transport',    'status' => 'active'],
            ['name' => 'Cinematic Films Lanka',   'category' => 'videographer', 'status' => 'active'],
            ['name' => 'Sweet Tier Cakes',        'category' => 'caterer',      'status' => 'active'],
            ['name' => 'Aurora Lighting Co.',     'category' => 'decor',        'status' => 'active'],
            ['name' => 'Heritage Band',           'category' => 'music',        'status' => 'inactive'],
            ['name' => 'Snapshot Booths',         'category' => 'photographer', 'status' => 'active'],
            ['name' => 'Bloom Box Rentals',       'category' => 'decor',        'status' => 'inactive'],
        ];

        return collect($named)->map(fn ($v) => Vendor::factory()->create($v + [
            'tenant_id' => $tid,
            'slug'      => Str::slug($v['name']).'-'.Str::lower(Str::random(4)),
        ]));
    }

    private function seedStaff(int $tid)
    {
        $named = [
            ['first_name' => 'Dilani',   'last_name' => 'Wickramasinghe', 'role' => 'coordinator'],
            ['first_name' => 'Tharindu', 'last_name' => 'Bandara',        'role' => 'manager'],
            ['first_name' => 'Maya',     'last_name' => 'Gunasekara',     'role' => 'assistant'],
            ['first_name' => 'Sahan',    'last_name' => 'Perera',         'role' => 'coordinator'],
            ['first_name' => 'Nadeesha', 'last_name' => 'Fernando',       'role' => 'photographer'],
            ['first_name' => 'Kasun',    'last_name' => 'Madushanka',     'role' => 'decorator'],
            ['first_name' => 'Iresha',   'last_name' => 'Pathirana',      'role' => 'assistant'],
            ['first_name' => 'Buddhika', 'last_name' => 'Ranaweera',      'role' => 'coordinator'],
            ['first_name' => 'Sewwandi', 'last_name' => 'Herath',         'role' => 'manager'],
            ['first_name' => 'Lahiru',   'last_name' => 'Jayasuriya',     'role' => 'photographer'],
        ];

        return collect($named)->map(fn ($s, $i) => Staff::factory()->create($s + [
            'tenant_id' => $tid,
            'email'     => Str::lower($s['first_name'].'.'.$s['last_name']).$i.'@showcase.eventpro.test',
        ]));
    }

    private function seedInquiries(int $tid, $clients, $venues, $packages)
    {
        $statuses = ['pending', 'pending', 'contacted', 'contacted', 'qualified', 'qualified', 'proposal_sent', 'proposal_sent', 'converted', 'converted', 'closed', 'closed'];
        $types    = ['wedding', 'corporate', 'birthday', 'anniversary', 'gala', 'conference'];

        return collect($statuses)->map(function ($status, $i) use ($tid, $clients, $venues, $packages, $types) {
            return Inquiry::factory()->create([
                'tenant_id'  => $tid,
                'client_id'  => $clients[$i % $clients->count()]->id,
                'venue_id'   => $venues[$i % $venues->count()]->id,
                'package_id' => $packages[$i % $packages->count()]->id,
                'event_type' => $types[$i % count($types)],
                'status'     => $status,
            ]);
        });
    }

    private function seedQuotations(int $tid, $clients, $venues, $packages, $inquiries)
    {
        $rows = [];
        // A spread of statuses; link some to inquiries.
        $plan = [
            ['state' => 'draft'],
            ['state' => 'draft'],
            ['state' => 'sent'],
            ['state' => 'sent'],
            ['state' => 'sent'],
            ['state' => 'accepted'],
            ['state' => 'accepted'],
            ['state' => 'accepted'],
            ['status' => 'viewed'],
            ['status' => 'rejected'],
            ['status' => 'expired'],
            ['status' => 'cancelled'],
        ];

        foreach ($plan as $i => $p) {
            $factory = Quotation::factory();
            if (isset($p['state'])) {
                $factory = $factory->{$p['state']}();
            }

            $attrs = [
                'tenant_id'  => $tid,
                'client_id'  => $clients[$i % $clients->count()]->id,
                'venue_id'   => $venues[$i % $venues->count()]->id,
                'package_id' => $packages[$i % $packages->count()]->id,
                'inquiry_id' => $i < $inquiries->count() ? $inquiries[$i]->id : null,
            ];
            if (isset($p['status'])) {
                $attrs['status'] = $p['status'];
            }

            $rows[] = $factory->create($attrs);
        }

        return collect($rows);
    }

    private function seedBookings(int $tid, $clients, $venues, $packages)
    {
        // [status, days-offset from today]. Past completed/cancelled + future pipeline.
        $plan = [
            ['completed', -180], ['completed', -120], ['completed', -75], ['completed', -40],
            ['cancelled', -90],  ['cancelled', -25],
            ['confirmed', 14],   ['confirmed', 30],   ['confirmed', 55],  ['confirmed', 80],
            ['tentative', 45],   ['tentative', 95],
            ['pending', 20],     ['pending', 60],     ['pending', 110],   ['pending', 150],
        ];
        $types = ['wedding', 'corporate', 'birthday', 'anniversary', 'conference'];

        $rows = [];
        foreach ($plan as $i => [$status, $offset]) {
            $venue   = $venues[$i % $venues->count()];
            $package = $packages[$i % $packages->count()];
            $client  = $clients[$i % $clients->count()];

            $total = (float) $package->base_price + (float) $venue->base_price;
            $paid  = match ($status) {
                'completed' => $total,
                'confirmed' => round($total * 0.4),
                'tentative' => round($total * 0.15),
                default     => 0,
            };

            $rows[] = Booking::factory()->create([
                'tenant_id'      => $tid,
                'venue_id'       => $venue->id,
                'client_id'      => $client->id,
                'package_id'     => $package->id,
                'event_type'     => $types[$i % count($types)],
                'event_date'     => now()->addDays($offset)->toDateString(),
                'guest_count'    => 80 + ($i * 15),
                'total_amount'   => $total,
                'paid_amount'    => $paid,
                'balance_amount' => $total - $paid,
                'status'         => $status,
            ]);
        }

        return collect($rows);
    }

    private function seedPayments(int $tid, $bookings): void
    {
        // Multiple payments against bookings that have money on them, plus a few
        // pending ones — 20 total.
        $count = 0;
        $target = 20;

        // Paid bookings get a deposit (completed) + sometimes an installment.
        foreach ($bookings as $booking) {
            if ($count >= $target) {
                break;
            }
            if ((float) $booking->paid_amount <= 0) {
                continue;
            }

            $deposit = round((float) $booking->paid_amount * 0.6);
            Payment::factory()->completed()->deposit()->create([
                'tenant_id'    => $tid,
                'booking_id'   => $booking->id,
                'client_id'    => $booking->client_id,
                'amount'       => $deposit,
                'payment_date' => now()->subDays(30)->toDateString(),
            ]);
            $count++;

            if ($count < $target) {
                Payment::factory()->completed()->create([
                    'tenant_id'        => $tid,
                    'booking_id'       => $booking->id,
                    'client_id'        => $booking->client_id,
                    'installment_name' => 'Second Installment',
                    'amount'           => (float) $booking->paid_amount - $deposit,
                    'payment_date'     => now()->subDays(10)->toDateString(),
                ]);
                $count++;
            }
        }

        // Top up with pending payments on the remaining bookings to reach the target.
        foreach ($bookings as $booking) {
            if ($count >= $target) {
                break;
            }
            Payment::factory()->pending()->create([
                'tenant_id'        => $tid,
                'booking_id'       => $booking->id,
                'client_id'        => $booking->client_id,
                'installment_name' => 'Balance',
                'amount'           => round((float) $booking->balance_amount * 0.5) ?: 50000,
            ]);
            $count++;
        }
    }

    private function seedTasks(int $tid, $staff, $bookings): void
    {
        $titles = [
            ['Confirm floral arrangements with vendor',        'high',   'in_progress'],
            ['Finalize catering menu tasting',                 'medium', 'pending'],
            ['Send balance payment reminder',                  'high',   'pending'],
            ['Book photographer walkthrough',                  'low',    'completed'],
            ['Prepare seating chart',                          'medium', 'in_progress'],
            ['Coordinate vendor load-in schedule',             'high',   'pending'],
            ['Order welcome signage',                          'low',    'completed'],
            ['Schedule final client review meeting',           'medium', 'pending'],
            ['Arrange transport for the wedding party',        'medium', 'pending'],
            ['Confirm AV and lighting setup',                  'high',   'in_progress'],
            ['Print event-day run sheets',                     'low',    'pending'],
            ['Follow up on overdue deposit',                   'urgent', 'overdue'],
            ['Reconcile vendor invoices',                      'medium', 'overdue'],
            ['Collect post-event feedback',                    'low',    'completed'],
        ];

        foreach ($titles as $i => [$title, $priority, $state]) {
            $factory = Task::factory();
            $attrs = [
                'tenant_id'   => $tid,
                'assigned_to' => $staff[$i % $staff->count()]->id,
                'title'       => $title,
                'priority'    => $priority,
            ];

            if ($state === 'overdue') {
                $factory = $factory->overdue();
            } elseif ($state === 'completed') {
                $factory = $factory->completed();
            } else {
                $attrs['status'] = $state;
            }

            $factory->create($attrs);
        }
    }
}
