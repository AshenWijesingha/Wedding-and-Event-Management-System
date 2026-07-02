<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The booking_vendor table is already created in 2024_01_02_000002_create_vendors_table.
        // This migration is kept only for environments where that earlier migration ran
        // without the pivot table. Guarded to stay idempotent and avoid "table already exists".
        if (Schema::hasTable('booking_vendor')) {
            return;
        }

        Schema::create('booking_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->text('service_description')->nullable();
            $table->decimal('agreed_amount', 14, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        // No-op: ownership of this table belongs to the vendors migration.
    }
};
