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
        Schema::table('feature_settings', function (Blueprint $table): void {
            $table->decimal('reseller_discount_percent', 5, 2)->nullable()->after('reseller_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_settings', function (Blueprint $table): void {
            $table->dropColumn('reseller_discount_percent');
        });
    }
};
