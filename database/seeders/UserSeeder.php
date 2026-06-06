<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin (no tenant)
        User::updateOrCreate(
            ['email' => 'admin@eventpro.io'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create tenant users
        $tenant = Tenant::where('slug', 'grand-vista')->first();

        if ($tenant) {
            User::updateOrCreate(
                ['email' => 'john@grandvista.example.com'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'John Manager',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            User::updateOrCreate(
                ['email' => 'sarah@grandvista.example.com'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Sarah Staff',
                    'password' => Hash::make('password'),
                    'role' => 'staff',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
