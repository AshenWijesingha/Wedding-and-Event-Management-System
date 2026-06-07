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

        // Seed roles/permissions for any test that boots a fresh database, so
        // role-gated routes and assignRole()/hasRole() behave like production.
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->seed(RolePermissionSeeder::class);
        }
    }
}
