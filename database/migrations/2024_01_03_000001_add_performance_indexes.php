<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bookings: tenant + status + date queries are very common
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'bookings_tenant_status');
            $table->index(['tenant_id', 'event_date'], 'bookings_tenant_date');
            $table->index(['venue_id', 'event_date', 'status'], 'bookings_venue_date_status');
            $table->index('client_id', 'bookings_client');
        });

        // Payments: tenant + status queries for financial summaries
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'payments_tenant_status');
            $table->index(['booking_id', 'status'], 'payments_booking_status');
            $table->index('due_date', 'payments_due_date');
        });

        // Inquiries: tenant + status
        Schema::table('inquiries', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'inquiries_tenant_status');
            $table->index('preferred_date', 'inquiries_preferred_date');
        });

        // Quotations: client + status
        Schema::table('quotations', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'quotations_tenant_status');
            $table->index('client_id', 'quotations_client');
        });

        // Tasks: staff + status + due date
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['staff_id', 'status'], 'tasks_staff_status');
            $table->index('due_date', 'tasks_due_date');
        });

        // Vendors: tenant + status
        Schema::table('vendors', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'vendors_tenant_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_tenant_status');
            $table->dropIndex('bookings_tenant_date');
            $table->dropIndex('bookings_venue_date_status');
            $table->dropIndex('bookings_client');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_tenant_status');
            $table->dropIndex('payments_booking_status');
            $table->dropIndex('payments_due_date');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex('inquiries_tenant_status');
            $table->dropIndex('inquiries_preferred_date');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('quotations_tenant_status');
            $table->dropIndex('quotations_client');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_staff_status');
            $table->dropIndex('tasks_due_date');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendors_tenant_status');
        });
    }
};
