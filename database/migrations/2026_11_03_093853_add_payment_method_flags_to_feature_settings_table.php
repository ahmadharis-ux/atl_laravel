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
        Schema::table('feature_settings', function (Blueprint $table) {
            $table->boolean('deposit_qris_enabled')->default(true);
            $table->boolean('deposit_transfer_enabled')->default(true);
            $table->boolean('deposit_ewallet_enabled')->default(true);
            $table->boolean('transaction_saldo_enabled')->default(true);
            $table->boolean('transaction_qris_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_settings', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_qris_enabled',
                'deposit_transfer_enabled',
                'deposit_ewallet_enabled',
                'transaction_saldo_enabled',
                'transaction_qris_enabled',
            ]);
        });
    }
};
