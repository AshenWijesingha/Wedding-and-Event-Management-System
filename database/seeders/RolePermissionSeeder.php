<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Venues
            'venues.view', 'venues.create', 'venues.edit', 'venues.delete',
            // Packages
            'packages.view', 'packages.create', 'packages.edit', 'packages.delete',
            // Clients
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            // Inquiries
            'inquiries.view', 'inquiries.create', 'inquiries.edit', 'inquiries.delete', 'inquiries.assign',
            // Quotations
            'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.delete', 'quotations.send',
            // Bookings
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete', 'bookings.confirm', 'bookings.cancel',
            // Payments
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete', 'payments.refund',
            // Reports
            'reports.view', 'reports.export',
            // Settings
            'settings.view', 'settings.edit',
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
        ];

        // Platform-level permissions, reserved for the super admin only. These
        // govern the cross-tenant platform area (tenant + plan management).
        $platformPermissions = [
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete', 'tenants.impersonate',
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
        ];

        foreach (array_merge($permissions, $platformPermissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Tenant owner: full authority over a single tenant (every tenant-scoped
        // permission) but none of the platform-level permissions.
        $tenantOwner = Role::firstOrCreate(['name' => 'tenant_owner', 'guard_name' => 'web']);
        $tenantOwner->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::whereIn('name', $permissions)->whereNotIn('name', [
            'settings.edit',
            'users.delete',
        ])->get());

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'venues.view', 'packages.view',
            'clients.view', 'clients.create', 'clients.edit',
            'inquiries.view', 'inquiries.create', 'inquiries.edit', 'inquiries.assign',
            'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.send',
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.confirm', 'bookings.cancel',
            'payments.view', 'payments.create',
            'reports.view',
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'venues.view', 'packages.view',
            'clients.view',
            'inquiries.view', 'inquiries.create',
            'quotations.view',
            'bookings.view',
            'payments.view',
        ]);

        $client = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $client->syncPermissions([
            'bookings.view',
            'payments.view',
            'quotations.view',
        ]);
    }
}
