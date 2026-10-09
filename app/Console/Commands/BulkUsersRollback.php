<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BulkUsersRollback extends Command
{
    protected $signature = 'users:bulk-rollback {--chunk=5000 : Jumlah baris yang dihapus per iterasi}';

    protected $description = 'Menghapus semua user hasil BulkUsersSeeder (email berawalan bulk-user-)';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $total = 0;

        do {
            $deleted = DB::table('users')
                ->where('email', 'like', 'bulk-user-%')
                ->limit($chunk)
                ->delete();

            $total += $deleted;
            $this->output->write("\rDihapus: {$total}");
        } while ($deleted > 0);

        $this->output->newLine();
        $this->info("Selesai. Total {$total} user bulk dihapus.");

        return self::SUCCESS;
    }
}
