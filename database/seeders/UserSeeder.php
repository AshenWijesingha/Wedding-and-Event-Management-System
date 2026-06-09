<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
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
        $superAdmin->syncRoles(['super_admin']);

        $tenant = Tenant::where('slug', 'grand-vista')->first();

        if ($tenant) {
            $admin = User::updateOrCreate(
                ['email' => 'nuwan@mangala.lk'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Nuwan Perera',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $admin->syncRoles(['admin']);

            $staff = User::updateOrCreate(
                ['email' => 'sanduni@mangala.lk'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Sanduni Fernando',
                    'password' => Hash::make('password'),
                    'role' => 'staff',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $staff->syncRoles(['staff']);
        }
    }
}
