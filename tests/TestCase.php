<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Inertia/Blade pages reference the Vite manifest. CI does not build
        // frontend assets, so stub Vite out to keep page-render tests independent
        // of `npm run build`.
        $this->withoutVite();

        // Seed roles/permissions for any test that boots a fresh database, so
        // role-gated routes and assignRole()/hasRole() behave like production.
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->seed(RolePermissionSeeder::class);
        }
    }
}
