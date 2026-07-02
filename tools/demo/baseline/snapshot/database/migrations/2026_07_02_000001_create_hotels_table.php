<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
