<?php

namespace App\Console\Commands;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Support\TransactionLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deposits:expire-pending {--limit=200 : Jumlah maksimum deposit per jalan}')]
#[Description('Kedaluwarsakan permintaan top up yang melewati batas waktu pembayaran')]
class ExpirePendingDeposits extends Command
{
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $deposits = Deposit::query()
            ->where('status', DepositStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($deposits->isEmpty()) {
            $this->info('Tidak ada top up Pending yang perlu dikedaluwarsakan.');

            return self::SUCCESS;
        }

        foreach ($deposits as $deposit) {
            $updated = Deposit::query()->whereKey($deposit->getKey())->update(['status' => DepositStatus::Expired]);

            if ($updated > 0) {
                TransactionLogger::warning('deposit.expired', 'Top up kedaluwarsa karena tidak dibayar.', [
                    'reference' => $deposit->reference,
                    'amount' => $deposit->amount,
                ], null, $deposit->user);
            }
        }

        $this->info("Dikedaluwarsakan {$deposits->count()} top up.");

        return self::SUCCESS;
    }
}
