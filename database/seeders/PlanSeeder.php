<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small venues with 1-2 halls',
                'price_monthly' => 49.00,
                'price_yearly' => 490.00,
                'features' => [
                    'core_booking',
                    'basic_reporting',
                    'email_notifications',
                ],
                'limits' => [
                    'venues' => 2,
                    'users' => 2,
                    'storage_gb' => 5,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For medium venues with 3-5 halls',
                'price_monthly' => 99.00,
                'price_yearly' => 990.00,
                'features' => [
                    'core_booking',
                    'advanced_reporting',
                    'email_notifications',
                    'sms_notifications',
                    'client_portal',
                    'custom_fields',
                ],
                'limits' => [
                    'venues' => 5,
                    'users' => 5,
                    'storage_gb' => 20,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For large venues with 5-10 halls',
                'price_monthly' => 199.00,
                'price_yearly' => 1990.00,
                'features' => [
                    'core_booking',
                    'advanced_reporting',
                    'email_notifications',
                    'sms_notifications',
                    'client_portal',
                    'custom_fields',
                    'api_access',
                    'vendor_management',
                    'staff_scheduling',
                ],
                'limits' => [
                    'venues' => 10,
                    'users' => 20,
                    'storage_gb' => 50,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For hotel chains and franchises',
                'price_monthly' => 499.00,
                'price_yearly' => 4990.00,
                'features' => [
                    'core_booking',
                    'advanced_reporting',
                    'email_notifications',
                    'sms_notifications',
                    'client_portal',
                    'custom_fields',
                    'api_access',
                    'vendor_management',
                    'staff_scheduling',
                    'white_label',
                    'custom_integrations',
                    'dedicated_support',
                    'multi_property',
                ],
                'limits' => [
                    'venues' => -1, // unlimited
                    'users' => -1, // unlimited
                    'storage_gb' => 200,
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
