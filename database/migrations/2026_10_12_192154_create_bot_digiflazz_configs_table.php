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
        Schema::create('bot_digiflazz_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default');
            $table->text('cookies_content');
            $table->string('csrf_token');
            $table->string('socket_id')->nullable();
            $table->string('xsrf_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('scraper_config')->nullable(); // pattern, filters, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_digiflazz_configs');
    }
};
