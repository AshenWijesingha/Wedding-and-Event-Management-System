<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
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
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Venues
        $venues = [
            [
                'name' => 'The Grand Ballroom',
                'slug' => 'the-grand-ballroom',
                'description' => 'Our flagship venue featuring crystal chandeliers and a capacity for up to 500 guests.',
                'capacity_min' => 100,
                'capacity_max' => 500,
                'base_price' => 8000,
                'weekend_surcharge' => 1500,
                'amenities' => ['parking', 'valet', 'catering', 'av_equipment', 'dance_floor', 'stage'],
                'status' => 'active',
            ],
            [
                'name' => 'Garden Terrace',
                'slug' => 'garden-terrace',
                'description' => 'An intimate outdoor space surrounded by manicured gardens. Perfect for smaller celebrations.',
                'capacity_min' => 20,
                'capacity_max' => 150,
                'base_price' => 3500,
                'weekend_surcharge' => 800,
                'amenities' => ['parking', 'wifi', 'catering'],
                'status' => 'active',
            ],
            [
                'name' => 'Rooftop Pavilion',
                'slug' => 'rooftop-pavilion',
                'description' => 'Stunning panoramic city views from our rooftop venue. Ideal for cocktail receptions.',
                'capacity_min' => 50,
                'capacity_max' => 200,
                'base_price' => 5500,
                'weekend_surcharge' => 1000,
                'amenities' => ['parking', 'bar', 'av_equipment'],
                'status' => 'active',
            ],
        ];

        $createdVenues = [];
        foreach ($venues as $venueData) {
            $createdVenues[] = Venue::updateOrCreate(['slug' => $venueData['slug']], $venueData);
        }

        // Packages
        $packages = [
            [
                'name' => 'Classic Wedding',
                'slug' => 'classic-wedding',
                'description' => 'Our most popular wedding package with everything you need for a perfect day.',
                'base_price' => 5000,
                'price_per_guest' => 85,
                'min_guests' => 50,
                'max_guests' => 300,
                'includes' => ['Floral arrangements', 'DJ & sound system', 'Wedding coordinator', 'Catering setup'],
                'status' => 'active',
            ],
            [
                'name' => 'Corporate Gala',
                'slug' => 'corporate-gala',
                'description' => 'Elevate your corporate events with our premium gala package.',
                'base_price' => 3000,
                'price_per_guest' => 60,
                'min_guests' => 30,
                'max_guests' => 500,
                'includes' => ['AV equipment', 'Stage setup', 'Catering', 'Registration desk'],
                'status' => 'active',
            ],
            [
                'name' => 'Intimate Celebration',
                'slug' => 'intimate-celebration',
                'description' => 'Perfect for milestone birthdays, anniversaries, and small gatherings.',
                'base_price' => 1500,
                'price_per_guest' => 45,
                'min_guests' => 10,
                'max_guests' => 80,
                'includes' => ['Basic decor', 'Sound system', 'Event coordinator'],
                'status' => 'active',
            ],
        ];

        $createdPackages = [];
        foreach ($packages as $packageData) {
            $createdPackages[] = Package::updateOrCreate(['slug' => $packageData['slug']], $packageData);
        }

        // Clients
        $clientsData = [
            ['first_name' => 'Emily', 'last_name' => 'Johnson', 'email' => 'emily.johnson@demo.test', 'phone' => '+1 555-1001'],
            ['first_name' => 'Michael', 'last_name' => 'Chen', 'email' => 'michael.chen@demo.test', 'phone' => '+1 555-1002'],
            ['first_name' => 'Sarah', 'last_name' => 'Williams', 'email' => 'sarah.williams@demo.test', 'phone' => '+1 555-1003'],
            ['first_name' => 'David', 'last_name' => 'Martinez', 'email' => 'david.martinez@demo.test', 'phone' => '+1 555-1004'],
        ];

        $createdClients = [];
        foreach ($clientsData as $clientData) {
            $createdClients[] = Client::updateOrCreate(
                ['email' => $clientData['email']],
                array_merge($clientData, ['status' => 'active'])
            );
        }

        // Bookings
        $bookingStatuses = ['confirmed', 'confirmed', 'tentative', 'completed'];
        for ($i = 0; $i < 4; $i++) {
            $eventDate = now()->addDays(rand(30, 365));
            Booking::updateOrCreate(
                ['booking_number' => sprintf('BK%s%04d', date('Y'), $i + 1)],
                [
                    'client_id'      => $createdClients[$i]->id,
                    'venue_id'       => $createdVenues[$i % count($createdVenues)]->id,
                    'package_id'     => $createdPackages[$i % count($createdPackages)]->id,
                    'event_type'     => $i === 1 ? 'corporate' : 'wedding',
                    'event_date'     => $bookingStatuses[$i] === 'completed'
                        ? now()->subDays(rand(10, 60))->toDateString()
                        : $eventDate->toDateString(),
                    'guest_count'    => rand(80, 250),
                    'total_amount'   => rand(8000, 30000),
                    'paid_amount'    => $bookingStatuses[$i] === 'completed' ? rand(8000, 30000) : rand(2000, 8000),
                    'balance_amount' => 0,
                    'status'         => $bookingStatuses[$i],
                ]
            );
        }

        // Vendors
        $vendorsData = [
            ['name' => 'Bloom & Co Florists', 'category' => 'florist', 'contact_name' => 'Rosa Bloom', 'email' => 'rosa@bloomco.demo', 'status' => 'active'],
            ['name' => 'Premier Photography', 'category' => 'photographer', 'contact_name' => 'James Hart', 'email' => 'james@premierphoto.demo', 'status' => 'active'],
            ['name' => 'Sounds Unlimited DJ', 'category' => 'entertainment', 'contact_name' => 'DJ Max', 'email' => 'dj@soundsunlimited.demo', 'status' => 'active'],
            ['name' => 'Gourmet Catering Co', 'category' => 'catering', 'contact_name' => 'Chef Marie', 'email' => 'chef@gourmetcc.demo', 'status' => 'active'],
        ];

        foreach ($vendorsData as $vendorData) {
            Vendor::updateOrCreate(
                ['email' => $vendorData['email']],
                array_merge($vendorData, ['slug' => Str::slug($vendorData['name'])])
            );
        }

        // Staff
        $staffData = [
            ['first_name' => 'Jessica', 'last_name' => 'Park', 'email' => 'jessica@grandvista.demo', 'role' => 'coordinator', 'status' => 'active'],
            ['first_name' => 'Tom', 'last_name' => 'Richards', 'email' => 'tom@grandvista.demo', 'role' => 'operations', 'status' => 'active'],
            ['first_name' => 'Priya', 'last_name' => 'Nair', 'email' => 'priya@grandvista.demo', 'role' => 'sales', 'status' => 'active'],
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
                    'staff_id'    => $createdStaff[$idx % count($createdStaff)]->id,
                    'title'       => $taskName,
                    'priority'    => ['low', 'medium', 'high'][$idx % 3],
                    'status'      => $idx < 2 ? 'completed' : 'pending',
                    'due_date'    => now()->addDays($idx * 5 + 3)->toDateString(),
                    'description' => "Demo task: {$taskName}",
                ]
            );
        }

        $this->command->info('Demo data seeded for tenant: ' . $tenant->name);
    }
}
