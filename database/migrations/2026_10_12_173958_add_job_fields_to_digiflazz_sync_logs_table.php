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
        Schema::table('digiflazz_sync_logs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('type');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->string('status')->default('running')->after('finished_at'); // running, completed, failed
            $table->boolean('include_pascabayar')->default(false)->after('status');
            $table->integer('total_logs')->default(0)->after('include_pascabayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digiflazz_sync_logs', function (Blueprint $table) {
            //
        });
    }
};
