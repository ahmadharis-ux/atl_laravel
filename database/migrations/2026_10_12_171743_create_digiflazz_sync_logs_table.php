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
        Schema::create('digiflazz_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->index();
            $table->string('type')->default('sync'); // sync, reconcile, etc.
            $table->text('message')->nullable();
            $table->string('level')->default('info'); // info, warning, error
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digiflazz_sync_logs');
    }
};
