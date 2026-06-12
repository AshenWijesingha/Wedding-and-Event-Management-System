<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway', 30)->nullable()->after('payment_method');
            $table->string('gateway_payment_id', 64)->nullable()->unique()->after('gateway');
            $table->smallInteger('gateway_status_code')->nullable()->after('gateway_payment_id');
            $table->char('currency', 3)->default('LKR')->after('amount');
            $table->json('gateway_response')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_payment_id']);
            $table->dropColumn(['gateway', 'gateway_payment_id', 'gateway_status_code', 'currency', 'gateway_response']);
        });
    }
};
