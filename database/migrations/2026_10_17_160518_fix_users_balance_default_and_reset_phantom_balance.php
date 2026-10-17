<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom `balance` dulu punya default 1.250.000 (placeholder sebelum modul
     * deposit ada), sehingga user baru otomatis mendapat saldo yang bisa
     * dibelanjakan. Default dikembalikan ke 0 dan saldo hantu milik user yang
     * belum pernah bertransaksi ikut direset.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('balance')->default(0)->change();
        });

        DB::table('users')
            ->where('balance', 1250000)
            ->whereNotExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('wallet_transactions')
                ->whereColumn('wallet_transactions.user_id', 'users.id'))
            ->whereNotExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('deposits')
                ->whereColumn('deposits.user_id', 'users.id'))
            ->update([
                'balance' => 0,
                'points' => 0,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('balance')->default(1250000)->change();
        });
    }
};
