<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Client;
use App\Models\CustomField;
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
use Database\Seeders\Data\EventVendors;
use Database\Seeders\Data\SriLankaHotels;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'grand-vista')->firstOrFail();
        $tenant->makeCurrent();

        // Admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.eventpro.test'],
            [
                'name' => 'Kasun Rajapaksha',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Venues — real leading Sri Lankan hotels and their reception halls.
        // Prune the legacy placeholder venues so the replacement is clean on
        // re-seed (bookings cascade, inquiries/quotations null out by FK rules).
        Venue::whereIn('slug', [
            'mahaweli-grand-ballroom', 'pol-watta-garden-terrace', 'galle-face-rooftop-pavilion',
        ])->delete();

        $createdVenues = [];
        foreach (SriLankaHotels::halls() as $venueData) {
            $createdVenues[] = Venue::updateOrCreate(['slug' => $venueData['slug']], $venueData);
        }

        // Packages — real Sri Lankan reception service packages.
        $createdPackages = [];
        foreach (SriLankaHotels::packages() as $packageData) {
            $createdPackages[] = Package::updateOrCreate(['slug' => $packageData['slug']], $packageData);
        }

        // Make every package available at every hall (venue_packages pivot).
        foreach ($createdPackages as $package) {
            $package->venues()->sync(collect($createdVenues)->pluck('id')->all());
        }

        // Clients
        $clientsData = [
            ['first_name' => 'Nimal', 'last_name' => 'Jayawardena', 'email' => 'nimal.jayawardena@demo.test', 'phone' => '+94 77 123 4501'],
            ['first_name' => 'Dilani', 'last_name' => 'Wickramasinghe', 'email' => 'dilani.wickramasinghe@demo.test', 'phone' => '+94 71 123 4502'],
            ['first_name' => 'Ruwan', 'last_name' => 'Bandara', 'email' => 'ruwan.bandara@demo.test', 'phone' => '+94 76 123 4503'],
            ['first_name' => 'Tharushi', 'last_name' => 'Senanayake', 'email' => 'tharushi.senanayake@demo.test', 'phone' => '+94 70 123 4504'],
        ];

        $createdClients = [];
        foreach ($clientsData as $clientData) {
            $createdClients[] = Client::updateOrCreate(
                ['email' => $clientData['email']],
                $clientData
            );
        }

        // Bookings
        $bookingStatuses = ['confirmed', 'confirmed', 'tentative', 'completed'];
        $createdBookings = [];
        for ($i = 0; $i < 4; $i++) {
            $eventDate = now()->addDays(rand(30, 365));
            $total = rand(800000, 3000000);
            $paid  = $bookingStatuses[$i] === 'completed' ? $total : rand(200000, (int) ($total * 0.5));

            $createdBookings[] = Booking::updateOrCreate(
                ['booking_number' => sprintf('BK%s%04d', date('Y'), $i + 1)],
                [
                    'client_id'      => $createdClients[$i]->id,
                    'venue_id'       => $createdVenues[$i % count($createdVenues)]->id,
                    'package_id'     => $createdPackages[$i % count($createdPackages)]->id,
                    'event_type'     => $i === 1 ? 'corporate' : 'wedding',
                    'event_date'     => $bookingStatuses[$i] === 'completed'
                        ? now()->subDays(rand(10, 60))->toDateString()
                        : $eventDate->toDateString(),
                    'setup_time'       => '08:00',
                    'event_start_time' => '10:00',
                    'event_end_time'   => '22:00',
                    'guest_count'    => rand(80, 250),
                    'total_amount'   => $total,
                    'paid_amount'    => $paid,
                    'balance_amount' => $total - $paid,
                    'status'         => $bookingStatuses[$i],
                ]
            );
        }

        // Vendors — real-style Sri Lankan event vendors (shared dataset).
        $createdVendors = [];
        foreach (EventVendors::all() as $vendorData) {
            $createdVendors[] = Vendor::updateOrCreate(
                ['email' => $vendorData['email']],
                array_merge($vendorData, ['slug' => Str::slug($vendorData['name'])])
            );
        }

        // Attach vendors to bookings (booking_vendor pivot)
        foreach ($createdBookings as $bIdx => $booking) {
            $vendorSet = [
                $createdVendors[$bIdx % count($createdVendors)]->id,
                $createdVendors[($bIdx + 1) % count($createdVendors)]->id,
            ];
            foreach ($vendorSet as $vendorId) {
                $booking->vendors()->syncWithoutDetaching([
                    $vendorId => [
                        'service_description' => 'Demo service for ' . $booking->booking_number,
                        'agreed_amount'       => rand(80000, 400000),
                        'status'              => 'confirmed',
                    ],
                ]);
            }
        }

        // Staff
        $staffData = [
            ['first_name' => 'Sachini', 'last_name' => 'Gunawardena', 'email' => 'sachini@mangala.demo', 'role' => 'coordinator', 'status' => 'active'],
            ['first_name' => 'Pasindu', 'last_name' => 'Madushanka', 'email' => 'pasindu@mangala.demo', 'role' => 'operations', 'status' => 'active'],
            ['first_name' => 'Ishara', 'last_name' => 'Dissanayake', 'email' => 'ishara@mangala.demo', 'role' => 'sales', 'status' => 'active'],
        ];

        $createdStaff = [];
        foreach ($staffData as $sd) {
            $createdStaff[] = Staff::updateOrCreate(['email' => $sd['email']], $sd);
        }

        // Tasks
        $taskNames = [
            'Confirm floral arrangements',
            'Send welcome pack to client',
            'Review catering menu',
            'Test AV equipment',
            'Prepare venue layout diagram',
        ];

        foreach ($taskNames as $idx => $taskName) {
            Task::updateOrCreate(
                ['title' => $taskName],
                [
                    'assigned_to' => $createdStaff[$idx % count($createdStaff)]->id,
                    'title'       => $taskName,
                    'priority'    => ['low', 'medium', 'high'][$idx % 3],
                    'status'      => $idx < 2 ? 'completed' : 'pending',
                    'due_date'    => now()->addDays($idx * 5 + 3)->toDateString(),
                    'description' => "Demo task: {$taskName}",
                ]
            );
        }

        // Inquiries
        $inquiriesData = [
            ['client' => 0, 'venue' => 0, 'package' => 0, 'event_type' => 'wedding',   'status' => 'pending',       'guests' => 180, 'message' => 'Looking for a grand wedding venue for next spring.'],
            ['client' => 1, 'venue' => 2, 'package' => 1, 'event_type' => 'corporate', 'status' => 'contacted',     'guests' => 120, 'message' => 'Annual company gala — need AV and stage.'],
            ['client' => 2, 'venue' => 1, 'package' => 2, 'event_type' => 'birthday',  'status' => 'qualified',     'guests' => 60,  'message' => 'Milestone 50th birthday celebration in the garden.'],
            ['client' => 3, 'venue' => 0, 'package' => 0, 'event_type' => 'wedding',   'status' => 'proposal_sent', 'guests' => 250, 'message' => 'Large traditional wedding, full package required.'],
            ['client' => 1, 'venue' => 2, 'package' => 2, 'event_type' => 'anniversary','status' => 'converted',    'guests' => 40,  'message' => 'Intimate 25th anniversary dinner with rooftop views.'],
        ];

        foreach ($inquiriesData as $idx => $inq) {
            Inquiry::updateOrCreate(
                ['inquiry_number' => sprintf('INQ%s%04d', date('Y'), $idx + 1)],
                [
                    'client_id'        => $createdClients[$inq['client']]->id,
                    'venue_id'         => $createdVenues[$inq['venue']]->id,
                    'package_id'       => $createdPackages[$inq['package']]->id,
                    'event_type'       => $inq['event_type'],
                    'preferred_date'   => now()->addDays(rand(45, 300))->toDateString(),
                    'guest_count'      => $inq['guests'],
                    'budget_range_min' => 500000,
                    'budget_range_max' => 5000000,
                    'message'          => $inq['message'],
                    'source'           => ['website', 'referral', 'social_media', 'phone'][$idx % 4],
                    'status'           => $inq['status'],
                ]
            );
        }

        // Quotations
        $quotationStates = [
            ['status' => 'draft',    'booking' => null],
            ['status' => 'sent',     'booking' => null],
            ['status' => 'accepted', 'booking' => 0],
            ['status' => 'expired',  'booking' => null],
        ];

        foreach ($quotationStates as $idx => $qs) {
            $items = [
                ['name' => 'Venue Hire',  'description' => 'Full-day exclusive venue access', 'quantity' => 1,   'unit_price' => 1200000, 'total' => 1200000],
                ['name' => 'Catering',    'description' => 'Three-course plated dinner',      'quantity' => 150, 'unit_price' => 6500,    'total' => 975000],
                ['name' => 'Decoration',  'description' => 'Floral and table styling',        'quantity' => 1,   'unit_price' => 350000,  'total' => 350000],
                ['name' => 'Photography', 'description' => 'Full event coverage',             'quantity' => 1,   'unit_price' => 180000,  'total' => 180000],
            ];
            $subtotal = array_sum(array_column($items, 'total'));
            $discount = (int) ($subtotal * 0.05);
            $tax      = (int) (($subtotal - $discount) * 0.1);
            $total    = $subtotal - $discount + $tax;

            Quotation::updateOrCreate(
                ['quotation_number' => sprintf('QT%s%04d', date('Y'), $idx + 1)],
                [
                    'client_id'            => $createdClients[$idx % count($createdClients)]->id,
                    'venue_id'             => $createdVenues[$idx % count($createdVenues)]->id,
                    'package_id'           => $createdPackages[$idx % count($createdPackages)]->id,
                    'booking_id'           => $qs['booking'] !== null ? $createdBookings[$qs['booking']]->id : null,
                    'event_date'           => now()->addDays(rand(60, 300))->toDateString(),
                    'guest_count'          => rand(80, 220),
                    'items'                => $items,
                    'subtotal'             => $subtotal,
                    'discount_amount'      => $discount,
                    'tax_amount'           => $tax,
                    'total_amount'         => $total,
                    'valid_until'          => $qs['status'] === 'expired'
                        ? now()->subDays(5)->toDateString()
                        : now()->addDays(21)->toDateString(),
                    'status'               => $qs['status'],
                    'terms_and_conditions' => '50% deposit required to confirm the booking. Balance due 14 days before the event.',
                    'sent_at'              => in_array($qs['status'], ['sent', 'accepted', 'expired'], true) ? now()->subDays(7) : null,
                    'accepted_at'          => $qs['status'] === 'accepted' ? now()->subDays(2) : null,
                ]
            );
        }

        // Payments (deposit + balance per booking; some overdue for reminder demo)
        $paySeq = 1;
        foreach ($createdBookings as $bIdx => $booking) {
            $deposit = (int) ($booking->total_amount * 0.5);

            Payment::updateOrCreate(
                ['payment_number' => sprintf('PAY%s%04d', date('Y'), $paySeq++)],
                [
                    'booking_id'       => $booking->id,
                    'client_id'        => $booking->client_id,
                    'installment_name' => 'Deposit',
                    'amount'           => $deposit,
                    'payment_method'   => 'bank_transfer',
                    'payment_date'     => now()->subDays(rand(20, 60))->toDateString(),
                    'reference_number' => 'REF' . strtoupper(Str::random(8)),
                    'status'           => 'completed',
                    'received_by'      => $admin->id,
                ]
            );

            $balanceStatus = $booking->status === 'completed' ? 'completed' : 'pending';
            Payment::updateOrCreate(
                ['payment_number' => sprintf('PAY%s%04d', date('Y'), $paySeq++)],
                [
                    'booking_id'       => $booking->id,
                    'client_id'        => $booking->client_id,
                    'installment_name' => 'Balance',
                    'amount'           => $booking->total_amount - $deposit,
                    'payment_method'   => 'credit_card',
                    'payment_date'     => $balanceStatus === 'completed' ? now()->subDays(rand(1, 15))->toDateString() : now()->toDateString(),
                    'reference_number' => $balanceStatus === 'completed' ? 'REF' . strtoupper(Str::random(8)) : null,
                    'status'           => $balanceStatus,
                    // Make one outstanding balance overdue to demo payment reminders
                    'due_date'         => $balanceStatus === 'pending'
                        ? now()->addDays($bIdx === 0 ? -3 : 14)->toDateString()
                        : null,
                ]
            );
        }

        // Custom Fields (demo of the extensible custom-field system)
        $customFields = [
            ['entity_type' => 'booking', 'name' => 'Dietary Requirements', 'slug' => 'dietary-requirements', 'type' => 'textarea', 'help_text' => 'Any allergies or dietary needs for catering.'],
            ['entity_type' => 'booking', 'name' => 'Theme Color',          'slug' => 'theme-color',          'type' => 'text',     'placeholder' => 'e.g. Burgundy & Gold'],
            ['entity_type' => 'client',  'name' => 'Preferred Contact',    'slug' => 'preferred-contact',    'type' => 'select',   'options' => ['Email', 'Phone', 'WhatsApp']],
        ];

        foreach ($customFields as $sort => $cf) {
            CustomField::updateOrCreate(
                ['entity_type' => $cf['entity_type'], 'slug' => $cf['slug']],
                array_merge($cf, [
                    'is_required'   => false,
                    'is_active'     => true,
                    'is_searchable' => false,
                    'show_on_list'  => false,
                    'sort_order'    => $sort,
                ])
            );
        }

        $this->command->info('Demo data seeded for tenant: ' . $tenant->name);
    }
}
