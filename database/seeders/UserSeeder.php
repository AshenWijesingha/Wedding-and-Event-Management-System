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
        User::create([
            'tenant_id' => null,
            'name' => 'Super Admin',
            'email' => 'admin@eventpro.io',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create tenant admin
        $tenant = Tenant::where('slug', 'grand-vista')->first();
        
        if ($tenant) {
            User::create([
                'tenant_id' => $tenant->id,
                'name' => 'John Manager',
                'email' => 'john@grandvista.example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Sarah Staff',
                'email' => 'sarah@grandvista.example.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
