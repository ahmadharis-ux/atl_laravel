<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BulkUsersSeeder extends Seeder
{
    /**
     * Jumlah baris default. Bisa dioverride lewat env BULK_USERS_COUNT.
     */
    public int $count = 3_000_000;

    /**
     * Ukuran per statement INSERT. Bisa dioverride lewat env BULK_USERS_CHUNK.
     */
    public int $chunkSize = 2000;

    /**
     * Jumlah baris saat ini di tabel users (dibuat updateOrCreate) dihitung dari 0 untuk
     * offset email? Tidak — nomor email memakai offset tetap agar idempotent saat diulang.
     */
    public function run(): void
    {
        $count = (int) (env('BULK_USERS_COUNT') ?: $this->count);
        $chunkSize = (int) (env('BULK_USERS_CHUNK') ?: $this->chunkSize);
        $domain = 'atlantic.test';

        $password = Hash::make('password');
        $now = now()->toDateTimeString();

        $firstNames = [
            'Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Gita', 'Hadi', 'Indra', 'Joko',
            'Kartika', 'Lina', 'Made', 'Nina', 'Oka', 'Putri', 'Rizky', 'Sari', 'Tono', 'Umar',
            'Vina', 'Wawan', 'Yanti', 'Zaki', 'Agus', 'Bayu', 'Dian', 'Ratna', 'Hendra', 'Maya',
        ];
        $lastNames = [
            'Pratama', 'Santoso', 'Wijaya', 'Kusuma', 'Nugroho', 'Lestari', 'Saputra', 'Halim',
            'Setiawan', 'Permata', 'Anggraini', 'Firmansyah', 'Hidayat', 'Maulana', 'Utami',
        ];

        DB::connection()->disableQueryLog();

        $bar = $this->command?->getOutput()->createProgressBar($count);
        $bar?->start();

        $processed = 0;

        for ($offset = 0; $offset < $count; $offset += $chunkSize) {
            $limit = min($chunkSize, $count - $offset);
            $rows = [];

            for ($i = 0; $i < $limit; $i++) {
                $n = $offset + $i + 1;

                $rows[] = [
                    'name' => $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)],
                    'email' => "bulk-user-{$n}@{$domain}",
                    'phone' => '08'.str_pad((string) mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
                    'email_verified_at' => $now,
                    'password' => $password,
                    'is_admin' => false,
                    'remember_token' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::transaction(fn () => DB::table('users')->insertOrIgnore($rows));

            $processed += $limit;
            $bar?->advance($limit);
        }

        $bar?->finish();
        $this->command?->newLine();
        $this->command?->info("Selesai. {$processed} baris user bulk diproses (email prefix: bulk-user-).");
        $this->command?->line('Rollback: php artisan users:bulk-rollback');
    }
}
