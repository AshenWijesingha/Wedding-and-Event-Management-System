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

        Tenant::create([
            'uuid' => Str::uuid(),
            'name' => 'Grand Vista Events',
            'slug' => 'grand-vista',
            'domain' => 'grandvista.localhost',
            'plan_id' => $plan?->id,
            'email' => 'info@grandvista.example.com',
            'phone' => '+1 555-0123',
            'primary_color' => '#3B82F6',
            'status' => 'active',
            'settings' => [
                'general' => [
                    'business_name' => 'Grand Vista Events',
                    'tagline' => 'Creating Unforgettable Moments',
                    'timezone' => 'America/New_York',
                ],
                'currency' => [
                    'code' => 'USD',
                    'symbol' => '$',
                    'position' => 'before',
                ],
                'booking' => [
                    'min_advance_days' => 14,
                    'max_advance_days' => 365,
                    'deposit_percentage' => 25,
                ],
                'contact' => [
                    'address' => '123 Event Boulevard',
                    'city' => 'New York',
                    'country' => 'United States',
                ],
            ],
        ]);
    }
}
