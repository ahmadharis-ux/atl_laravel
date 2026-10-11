<?php

namespace App\Support;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sumber kebenaran mutasi saldo user (ledger append-only).
 *
 * Setiap perubahan saldo wajib lewat sini: baris di `wallet_transactions`
 * dicatat sekaligus, kolom `users.balance` hanya menyimpan saldo terkini
 * agar cepat dibaca. Semua berjalan dalam satu transaksi DB dengan row lock
 * supaya tidak ada saldo yang hilang saat balapan (race condition).
 */
final class WalletLedger
{
    /**
     * Menambah saldo user dan mencatat baris ledger "masuk".
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function credit(
        User $user,
        int $amount,
        WalletTransactionType $type,
        ?Model $reference = null,
        ?string $description = null,
        array $metadata = [],
    ): WalletTransaction {
        return self::apply($user, WalletTransactionDirection::Credit, $amount, $type, $reference, $description, $metadata);
    }

    /**
     * Memotong saldo user dan mencatat baris ledger "keluar".
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function debit(
        User $user,
        int $amount,
        WalletTransactionType $type,
        ?Model $reference = null,
        ?string $description = null,
        array $metadata = [],
    ): WalletTransaction {
        return self::apply($user, WalletTransactionDirection::Debit, $amount, $type, $reference, $description, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private static function apply(
        User $user,
        WalletTransactionDirection $direction,
        int $amount,
        WalletTransactionType $type,
        ?Model $reference,
        ?string $description,
        array $metadata,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal transaksi harus lebih dari nol.',
            ]);
        }

        return DB::transaction(function () use ($user, $direction, $amount, $type, $reference, $description, $metadata): WalletTransaction {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $before = (int) $locked->balance;
            $after = $before + ($direction->sign() * $amount);

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'balance' => 'Saldo tidak mencukupi untuk transaksi ini.',
                ]);
            }

            $locked->forceFill(['balance' => $after])->save();

            $transaction = $locked->walletTransactions()->create([
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);

            $user->setAttribute('balance', $after);

            return $transaction;
        });
    }
}
