<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('master_bank_id')
                ->nullable()
                ->after('payment_code')
                ->constrained('master_banks')
                ->nullOnDelete();
            $table->integer('fee')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('master_bank_id');
            $table->dropColumn('fee');
        });
    }
};
