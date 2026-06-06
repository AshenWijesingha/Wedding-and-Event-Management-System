<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // clients: email lookup without tenant filter (password resets, login checks)
        Schema::table('clients', function (Blueprint $table) {
            $table->index('email', 'clients_email');
        });

        // inquiries: created_at for recent-activity dashboard queries
        Schema::table('inquiries', function (Blueprint $table) {
            $table->index('created_at', 'inquiries_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_email');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex('inquiries_created_at');
        });
    }
};
