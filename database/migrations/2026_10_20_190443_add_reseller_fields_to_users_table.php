<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_reseller')->default(false);
            $table->string('reseller_api_key', 64)->nullable()->unique();
            $table->string('reseller_api_key_hint', 20)->nullable();
            $table->timestamp('reseller_since')->nullable();

            $table->index('reseller_since');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['reseller_since']);
            $table->dropUnique(['reseller_api_key']);
            $table->dropColumn([
                'is_reseller',
                'reseller_api_key',
                'reseller_api_key_hint',
                'reseller_since',
            ]);
        });
    }
};
