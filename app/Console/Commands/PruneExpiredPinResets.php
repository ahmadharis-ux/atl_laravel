<?php

namespace App\Console\Commands;

use App\Models\PinResetOtp;
use App\Models\PinResetToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pin:prune')]
#[Description('Hapus kode OTP dan token reset PIN yang sudah kedaluwarsa atau terpakai')]
class PruneExpiredPinResets extends Command
{
    public function handle(): int
    {
        $otps = PinResetOtp::query()
            ->where(function ($query): void {
                $query->where('expires_at', '<', now())
                    ->orWhereNotNull('consumed_at');
            })
            ->delete();

        $tokens = PinResetToken::query()
            ->where(function ($query): void {
                $query->where('expires_at', '<', now())
                    ->orWhereNotNull('consumed_at');
            })
            ->delete();

        $this->info("Dihapus {$otps} OTP dan {$tokens} token reset PIN.");

        return self::SUCCESS;
    }
}
