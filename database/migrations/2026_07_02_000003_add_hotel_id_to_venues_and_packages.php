<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['venues', 'packages'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('hotel_id')->nullable()->after('tenant_id')
                    ->constrained('hotels')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['venues', 'packages'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropConstrainedForeignId('hotel_id');
            });
        }
    }
};
