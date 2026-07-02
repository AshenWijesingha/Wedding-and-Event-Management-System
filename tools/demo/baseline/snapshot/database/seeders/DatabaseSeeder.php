<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            TenantSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            DemoDataSeeder::class,
            ShowcaseSeeder::class,
        ]);
    }
}
