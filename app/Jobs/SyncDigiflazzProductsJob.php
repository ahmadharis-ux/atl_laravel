<?php

namespace App\Jobs;

use App\Models\DigiflazzSyncLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class SyncDigiflazzProductsJob implements ShouldQueue
{
    use Queueable;

    public string $jobId;

    public function __construct(
        public bool $includePascabayar = false
    ) {
        $this->jobId = (string) Str::uuid();
    }

    public function handle(): void
    {
        $job = DigiflazzSyncLog::createJob($this->jobId, $this->includePascabayar);

        $job->addLog('info', "Memulai sinkronisasi produk Digiflazz (job_id: {$this->jobId})");

        try {
            $job->addLog('info', 'Memulai sinkronisasi prabayar...');
            $prepaid = Artisan::call('digiflazz:sync-products');
            $job->addLog('info', "Sinkronisasi prabayar selesai (exit_code: {$prepaid})");

            if ($this->includePascabayar) {
                $job->addLog('info', 'Memulai sinkronisasi pascabayar...');
                $pasca = Artisan::call('digiflazz:sync-products', ['--pasca' => true]);
                $job->addLog('info', "Sinkronisasi pascabayar selesai (exit_code: {$pasca})");
            }

            $job->addLog('info', 'Semua sinkronisasi selesai');
            $job->markCompleted();
        } catch (\Throwable $e) {
            $job->addLog('error', 'Sinkronisasi Digiflazz gagal', ['error' => $e->getMessage()]);
            $job->markFailed();
            throw $e;
        }
    }
}
