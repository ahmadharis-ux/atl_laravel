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
        Schema::create('bot_digiflazz_sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('bot_digiflazz_configs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('bot_digiflazz_products')->cascadeOnDelete();
            $table->string('seller_name')->index();
            $table->string('seller_sku_code')->nullable();
            $table->string('seller_connection_type')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('unlimited_stock')->default(false);
            $table->boolean('status')->default(true);
            $table->string('start_cut_off')->nullable();
            $table->string('end_cut_off')->nullable();
            $table->boolean('faktur')->default(false);
            $table->boolean('multi')->default(false);
            $table->unsignedInteger('multi_counter')->default(0);
            $table->string('seller_sku_id')->nullable();
            $table->string('seller_sku_id_int')->nullable();
            $table->boolean('is_seller_favorite')->default(false);
            $table->string('rating')->nullable();
            $table->string('rating_qty')->nullable();
            $table->string('deskripsi')->nullable();
            $table->json('seller_details')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['config_id', 'seller_name']);
            $table->index(['product_id', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_digiflazz_sellers');
    }
};
