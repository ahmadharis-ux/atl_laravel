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
            $table->string('idempotency_key', 50)->nullable()->after('order_number');
            $table->unique(['user_id', 'idempotency_key']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('reseller_webhook_url')->nullable()->after('reseller_last_api_at');
            $table->text('reseller_webhook_secret')->nullable()->after('reseller_webhook_url');
            $table->string('reseller_webhook_secret_hint', 30)->nullable()->after('reseller_webhook_secret');
            $table->timestamp('reseller_webhook_last_sent_at')->nullable()->after('reseller_webhook_secret_hint');
            $table->string('reseller_webhook_last_status', 20)->nullable()->after('reseller_webhook_last_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'reseller_webhook_url',
                'reseller_webhook_secret',
                'reseller_webhook_secret_hint',
                'reseller_webhook_last_sent_at',
                'reseller_webhook_last_status',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
