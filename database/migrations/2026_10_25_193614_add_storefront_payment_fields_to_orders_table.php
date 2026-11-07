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
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('storefront_key', 64)->nullable()->unique();
            $table->string('refund_status', 24)->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->string('refund_transaction_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['storefront_key']);
            $table->dropColumn([
                'storefront_key',
                'refund_status',
                'refund_requested_at',
                'refund_transaction_id',
            ]);
        });
    }
};
