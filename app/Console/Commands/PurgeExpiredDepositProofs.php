<?php

namespace App\Console\Commands;

use App\Models\DepositProof;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

#[Signature('deposits:purge-expired-proofs {--limit=200 : Jumlah maksimum bukti yang dihapus per jalan}')]
#[Description('Hapus bukti pembayaran privat 90 hari setelah top up mencapai status final')]
class PurgeExpiredDepositProofs extends Command
{
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $cutoff = now()->subDays(90);
        $proofs = DepositProof::query()
            ->whereHas('deposit', fn ($query) => $query
                ->whereNotNull('finalized_at')
                ->where('finalized_at', '<=', $cutoff))
            ->orderBy('id')
            ->limit($limit)
            ->get();
        $failed = false;
        $deleted = 0;

        foreach ($proofs as $proof) {
            $disk = Storage::disk($proof->disk);

            if ($disk->exists($proof->file_path) && ! $disk->delete($proof->file_path)) {
                Log::error('Failed to delete an expired deposit proof.', [
                    'proof_id' => $proof->getKey(),
                    'deposit_id' => $proof->deposit_id,
                ]);
                $failed = true;

                continue;
            }

            $proof->delete();
            $deleted++;
        }

        $this->info("Dihapus {$deleted} bukti top up yang telah melewati masa retensi.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
