<?php

namespace Database\Factories;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(10_000, 500_000);
        $before = fake()->numberBetween($amount, 2_000_000);

        return [
            'user_id' => User::factory(),
            'type' => WalletTransactionType::Deposit,
            'direction' => WalletTransactionDirection::Credit,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $before + $amount,
            'reference_type' => null,
            'reference_id' => null,
            'description' => 'Top up saldo.',
            'metadata' => null,
        ];
    }

    public function debit(): static
    {
        return $this->state(function (array $attributes): array {
            $before = max($attributes['balance_after'], $attributes['amount']);

            return [
                'type' => WalletTransactionType::Purchase,
                'direction' => WalletTransactionDirection::Debit,
                'balance_before' => $before,
                'balance_after' => $before - $attributes['amount'],
                'description' => 'Pembelian produk.',
            ];
        });
    }
}
