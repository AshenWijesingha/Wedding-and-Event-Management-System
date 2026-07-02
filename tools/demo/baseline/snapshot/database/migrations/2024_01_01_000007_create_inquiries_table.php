<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('inquiry_number', 50);
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('event_type', 50);
            $table->date('preferred_date')->nullable();
            $table->date('alternate_date')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->decimal('budget_range_min', 14, 2)->nullable();
            $table->decimal('budget_range_max', 14, 2)->nullable();
            $table->text('message')->nullable();
            $table->string('source', 50)->nullable();
            $table->enum('status', ['pending', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'converted', 'closed'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'inquiry_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
