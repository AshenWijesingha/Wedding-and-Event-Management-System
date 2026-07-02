<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The users.role column was an enum, which becomes a brittle CHECK constraint
 * (notably on SQLite) that rejects any newly introduced role such as
 * `tenant_owner`. Roles are already validated at the application layer
 * (UserController Rule::in / RolePermissionSeeder), so store the column as a
 * plain string and let the app own the allowed set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin', 'manager', 'staff', 'client'])->default('staff')->change();
        });
    }
};
