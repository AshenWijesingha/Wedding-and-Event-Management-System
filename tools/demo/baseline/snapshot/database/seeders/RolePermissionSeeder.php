<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Hotels
            'hotels.view', 'hotels.create', 'hotels.edit', 'hotels.delete', 'hotels.submit',
            'venues.submit', 'packages.submit',
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
            // Vendors
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
            // Tasks
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
            // Custom fields
            'custom_fields.view', 'custom_fields.create', 'custom_fields.edit', 'custom_fields.delete',
            // Staff (employee records)
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
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
            'approvals.view', 'approvals.review',
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

        // Admin: full tenant authority except editing settings and deleting users.
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::whereIn('name', $permissions)->whereNotIn('name', [
            'settings.edit',
            'users.delete',
        ])->get());

        // Manager: sales/operations. Create/edit across the pipeline; tasks; view reports.
        // No settings, no user management, no vendors/custom-fields/staff management, no deletes.
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'hotels.view', 'hotels.create', 'hotels.edit', 'hotels.submit',
            'venues.view', 'venues.submit', 'packages.view', 'packages.submit',
            'clients.view', 'clients.create', 'clients.edit',
            'inquiries.view', 'inquiries.create', 'inquiries.edit', 'inquiries.assign',
            'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.send',
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.confirm', 'bookings.cancel',
            'payments.view', 'payments.create',
            'tasks.view', 'tasks.create', 'tasks.edit',
            'reports.view',
        ]);

        // Staff: mostly read-only execution. View pipeline, log inquiries, see tasks.
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'hotels.view',
            'venues.view', 'packages.view',
            'clients.view',
            'inquiries.view', 'inquiries.create',
            'quotations.view',
            'bookings.view',
            'payments.view',
            'tasks.view',
        ]);

        $client = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $client->syncPermissions([
            'bookings.view',
            'payments.view',
            'quotations.view',
        ]);
    }
}
