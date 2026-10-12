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
        Schema::create('bot_digiflazz_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('bot_digiflazz_configs')->cascadeOnDelete();
            $table->string('job_id')->index();
            $table->string('type')->default('scrape'); // scrape, scrape_and_update
            $table->string('status')->default('running'); // running, completed, failed
            $table->text('message')->nullable();
            $table->string('level')->default('info');
            $table->json('context')->nullable();
            $table->json('config_snapshot')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('total_logs')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_digiflazz_sync_logs');
    }
};
