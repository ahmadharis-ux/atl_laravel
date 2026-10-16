<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wilayah (region) produk topup game, diekstrak dari token kurung pada
     * nama produk Digiflazz (mis. "ML 112 Diamonds (Malaysia)" -> "Malaysia").
     * Null berarti produk umum / tanpa wilayah spesifik.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('region')->nullable()->after('name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
};
