<?php

namespace App\Support;

use App\Enums\DepositStatus;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\User;
use App\Notifications\TransactionResult;
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
    /**
     * Tandai top up lunas dan tambahkan saldo.
     *
     * Saldo yang dikreditkan = nominal diterima − biaya admin. Bila admin tidak
     * mengirim nominal diterima, dianggap terbayar penuh (nominal + biaya admin)
     * sehingga saldo bertambah sebesar nominal top up.
     */
    public static function settle(Deposit $deposit, ?User $actor = null, ?int $receivedAmount = null): bool
    {
        return DB::transaction(function () use ($deposit, $actor, $receivedAmount): bool {
            $locked = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [DepositStatus::Paid, DepositStatus::Failed], true)) {
                return false;
            }

            $received = $receivedAmount ?? $locked->totalDue();
            $credited = $locked->creditedFrom($received);

            $locked->update([
                'status' => DepositStatus::Paid,
                'paid_at' => now(),
                'received_amount' => $received,
            ]);

            $owner = $locked->user()->firstOrFail();

            if ($credited > 0) {
                WalletLedger::credit(
                    $owner,
                    $credited,
                    WalletTransactionType::Deposit,
                    $locked,
                    'Top up saldo '.$locked->reference,
                );

                $owner->addPoint();

                $owner->notify(new TransactionResult(
                    title: 'Top up berhasil',
                    body: 'Top up '.$locked->reference.' (Rp '.number_format($credited, 0, ',', '.').') berhasil. Saldo telah ditambahkan ke akunmu.',
                    level: 'success',
                    icon: 'account_balance_wallet',
                    url: route('deposits.show', $locked),
                    context: ['type' => 'deposit', 'reference' => $locked->reference],
                ));
            }

            TransactionLogger::info('deposit.paid', 'Top up saldo berhasil dilunasi.', [
                'reference' => $locked->reference,
                'amount' => $locked->amount,
                'fee' => $locked->fee,
                'received_amount' => $received,
                'credited' => $credited,
                'settled_by' => $actor?->getKey(),
            ], null, $owner);

            $deposit->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }
}
