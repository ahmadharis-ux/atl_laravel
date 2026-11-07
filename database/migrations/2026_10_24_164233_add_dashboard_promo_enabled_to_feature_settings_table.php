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
            $table->boolean('dashboard_promo_enabled')->default(true)->after('merchandise_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_settings', function (Blueprint $table) {
            $table->dropColumn('dashboard_promo_enabled');
        });
    }
};
