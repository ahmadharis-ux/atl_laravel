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
        Schema::create('bot_digiflazz_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('bot_digiflazz_configs')->cascadeOnDelete();
            $table->string('digiflazz_product_id')->index();
            $table->string('product_code')->index();
            $table->string('product_name');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('type')->nullable();
            $table->string('seller')->nullable();
            $table->string('seller_sku_code')->nullable();
            $table->string('seller_connection_type')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('unlimited_stock')->default(false);
            $table->boolean('status')->default(true);
            $table->string('start_cut_off')->nullable();
            $table->string('end_cut_off')->nullable();
            $table->string('description')->nullable();
            $table->boolean('faktur')->default(false);
            $table->boolean('multi')->default(false);
            $table->unsignedInteger('multi_counter')->default(0);
            $table->string('seller_sku_id')->nullable();
            $table->string('seller_sku_id_int')->nullable();
            $table->json('seller_details')->nullable();
            $table->string('last_update')->nullable();
            $table->boolean('is_seller_favorite')->default(false);
            $table->string('rating')->nullable();
            $table->string('rating_qty')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'digiflazz_product_id']);
            $table->index(['config_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_digiflazz_products');
    }
};
