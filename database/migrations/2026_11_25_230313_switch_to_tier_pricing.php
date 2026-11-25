<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harga kini mengikuti level akun (Silver/Gold/Platinum) untuk web dan API,
 * menggantikan diskon reseller global dan harga reseller per produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table): void {
            $table->decimal('silver_discount_percent', 5, 2)->default(0);
            $table->decimal('gold_discount_percent', 5, 2)->default(0);
            $table->decimal('platinum_discount_percent', 5, 2)->default(0);
        });

        Schema::table('feature_settings', function (Blueprint $table): void {
            $table->dropColumn('reseller_discount_percent');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('reseller_price_override');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('reseller_price_override')->nullable();
        });

        Schema::table('feature_settings', function (Blueprint $table): void {
            $table->decimal('reseller_discount_percent', 5, 2)->nullable();
        });

        Schema::table('loyalty_settings', function (Blueprint $table): void {
            $table->dropColumn(['silver_discount_percent', 'gold_discount_percent', 'platinum_discount_percent']);
        });
    }
};
