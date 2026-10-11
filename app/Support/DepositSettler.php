<?php

namespace App\Support;

use App\Enums\DepositStatus;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menandai top up sebagai lunas dan menambah saldo lewat ledger.
 *
 * Dipakai bersama oleh konfirmasi pembayaran user (mode development) dan
 * aksi admin (`topups.update`) supaya tidak ada logika kredit yang terduplikasi.
 * Idempoten: deposit yang sudah lunas/gagal tidak dikredit ulang.
 */
final class DepositSettler
{
    public static function settle(Deposit $deposit, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($deposit, $actor): bool {
            $locked = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [DepositStatus::Paid, DepositStatus::Failed], true)) {
                return false;
            }

            $locked->update([
                'status' => DepositStatus::Paid,
                'paid_at' => now(),
            ]);

            $owner = $locked->user()->firstOrFail();

            WalletLedger::credit(
                $owner,
                (int) $locked->amount,
                WalletTransactionType::Deposit,
                $locked,
                'Top up saldo '.$locked->reference,
            );

            TransactionLogger::info('deposit.paid', 'Top up saldo berhasil dilunasi.', [
                'reference' => $locked->reference,
                'amount' => $locked->amount,
                'settled_by' => $actor?->getKey(),
            ], null, $owner);

            $deposit->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }
}
