<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = Plan::where('slug', 'professional')->first();

        Tenant::updateOrCreate(
            ['slug' => 'grand-vista'],
            [
                'name' => 'Mangala Events (Pvt) Ltd',
                'domain' => 'mangala.localhost',
                'plan_id' => $plan?->id,
                'email' => 'info@mangala.lk',
                'phone' => '+94 11 234 5678',
                'primary_color' => '#3B82F6',
                'status' => 'active',
                'settings' => [
                    'general' => [
                        'business_name' => 'Mangala Events (Pvt) Ltd',
                        'tagline' => 'Creating Unforgettable Moments',
                        'timezone' => 'Asia/Colombo',
                    ],
                    'currency' => ['code' => 'LKR', 'symbol' => 'Rs', 'position' => 'before'],
                    'booking' => ['min_advance_days' => 14, 'max_advance_days' => 365, 'deposit_percentage' => 25],
                    'contact' => ['address' => '45, Galle Road', 'city' => 'Colombo 03', 'country' => 'Sri Lanka'],
                ],
            ]
        );
    }
}
