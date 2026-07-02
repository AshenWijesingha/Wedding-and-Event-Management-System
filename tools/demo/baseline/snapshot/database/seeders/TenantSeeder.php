<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = Plan::where('slug', 'professional')->first();

        // Slug stays 'grand-vista' — DemoDataSeeder resolves this tenant by slug.
        Tenant::updateOrCreate(
            ['slug' => 'grand-vista'],
            [
                'name' => 'EventPro',
                'domain' => 'eventpro.localhost',
                'plan_id' => $plan?->id,
                'email' => 'hello@eventpro.lk',
                'phone' => '+94 11 234 5678',
                'primary_color' => '#3B82F6',
                'status' => 'active',
                'settings' => [
                    'general' => [
                        'business_name' => 'EventPro',
                        'tagline' => 'Creating Unforgettable Moments',
                        'timezone' => 'Asia/Colombo',
                    ],
                    'currency' => ['code' => 'LKR', 'symbol' => 'Rs', 'position' => 'before'],
                    'booking' => ['min_advance_days' => 14, 'max_advance_days' => 365, 'deposit_percentage' => 25],
                    'contact' => ['address' => 'No. 45, Galle Road', 'city' => 'Colombo 03', 'country' => 'Sri Lanka'],
                    'social' => [
                        'facebook' => 'https://facebook.com/eventpro.lk',
                        'instagram' => 'https://instagram.com/eventpro.lk',
                        'linkedin' => 'https://linkedin.com/company/eventpro-lk',
                    ],
                ],
            ]
        );
    }
}
