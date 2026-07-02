<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 50); // photographer, caterer, florist, music, decor, transport, etc.
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_rate', 14, 2)->nullable();
            $table->string('rate_type', 20)->default('fixed'); // fixed, hourly, per_event
            $table->json('services')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('booking_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('service_description')->nullable();
            $table->decimal('agreed_amount', 14, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_vendor');
        Schema::dropIfExists('vendors');
    }
};
