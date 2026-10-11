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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('saldo')->after('status');
            $table->string('payment_channel')->nullable()->after('payment_method');
            $table->string('payment_code')->nullable()->after('payment_channel');
            $table->timestamp('paid_at')->nullable()->after('payment_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_channel', 'payment_code', 'paid_at']);
        });
    }
};
