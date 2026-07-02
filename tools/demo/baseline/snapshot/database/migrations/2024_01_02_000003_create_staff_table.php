<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role', 50)->default('staff'); // coordinator, manager, photographer, etc.
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['booking_id']);
            $table->index(['assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('staff');
    }
};
