<?php

namespace App\Support;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\DepositProof;
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
            $isManual = in_array($locked->payment_method, [PaymentMethod::VirtualAccount, PaymentMethod::EWallet], true);
            $proof = null;

            if ($isManual) {
                $proof = $locked->proofs()->lockForUpdate()->first();

                if ($locked->status !== DepositStatus::UnderReview || $proof === null || $proof->review_action !== null || $actor === null) {
                    return false;
                }
            } elseif ($locked->status !== DepositStatus::Pending) {
                return false;
            }

            $received = $receivedAmount ?? $locked->totalDue();
            $credited = $locked->creditedFrom($received);

            if ($isManual && $credited < 1) {
                return false;
            }

            $proof?->update([
                'reviewed_by' => $actor?->getKey(),
                'review_action' => DepositProof::ACTION_APPROVED,
                'review_reason' => null,
                'reviewed_at' => now(),
            ]);

            $finalizedAt = now();

            $locked->update([
                'status' => DepositStatus::Paid,
                'paid_at' => now(),
                'finalized_at' => $finalizedAt,
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

                $owner->changePoints(1, PointTransactionType::TopupReward, 'Bonus top up '.$locked->reference, $locked);

                $owner->notify(new TransactionResult(
                    title: 'Isi saldo berhasil',
                    body: 'Pengisian saldo '.$locked->reference.' (Rp '.number_format($credited, 0, ',', '.').') berhasil. Saldo telah ditambahkan ke akunmu.',
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
