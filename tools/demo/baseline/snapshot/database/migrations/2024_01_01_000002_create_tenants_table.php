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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('domain')->unique()->nullable();
            $table->string('database')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->json('settings')->nullable();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
