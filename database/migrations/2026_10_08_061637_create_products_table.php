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
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('code')->unique();
            $table->string('product_ref')->nullable();
            $table->string('name');
            $table->enum('payment_type', ['prabayar', 'pascabayar'])->index();

            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();

            $table->text('description')->nullable();

            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('max_price')->default(0);
            $table->string('markup_type')->default('fixed');
            $table->decimal('markup_value', 15, 2)->default(0);

            $table->unsignedInteger('stock')->default(0);
            $table->boolean('unlimited_stock')->default(false);
            $table->time('start_cut_off')->nullable();
            $table->time('end_cut_off')->nullable();
            $table->boolean('faktur')->default(false);
            $table->boolean('multi')->default(false);
            $table->unsignedInteger('multi_counter')->default(0);

            $table->string('seller_sku_code')->nullable();
            $table->string('seller_sku_id')->nullable();
            $table->unsignedBigInteger('seller_sku_id_int')->nullable();
            $table->string('seller_connection_type')->nullable();
            $table->boolean('seller_fatur_pajak')->default(false);
            $table->text('seller_sku_desc')->nullable();
            $table->json('seller_details')->nullable();

            $table->boolean('status')->default(true)->index();
            $table->tinyInteger('status_seller_sku')->default(0);
            $table->dateTime('last_update')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('change')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
