<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pending/failed gateway payments have no payment_date until the webhook
     * confirms them, so the column must allow null.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable(false)->change();
        });
    }
};
