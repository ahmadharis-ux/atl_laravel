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
            $table->string('provider')->nullable()->after('status');
            $table->string('provider_ref')->nullable()->unique()->after('provider');
            $table->string('provider_sn')->nullable()->after('provider_ref');
            $table->string('provider_status')->nullable()->after('provider_sn');
            $table->unsignedBigInteger('provider_price')->nullable()->after('provider_status');
            $table->unsignedBigInteger('provider_admin')->nullable()->after('provider_price');
            $table->json('provider_payload')->nullable()->after('provider_admin');
            $table->timestamp('processed_at')->nullable()->after('provider_payload');
            $table->timestamp('refunded_at')->nullable()->after('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['provider_ref']);
            $table->dropColumn([
                'provider', 'provider_ref', 'provider_sn', 'provider_status',
                'provider_price', 'provider_admin', 'provider_payload',
                'processed_at', 'refunded_at',
            ]);
        });
    }
};
