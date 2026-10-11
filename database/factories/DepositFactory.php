<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference' => 'DEP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'amount' => fake()->numberBetween(10_000, 1_000_000),
            'payment_method' => PaymentMethod::Qris,
            'payment_channel' => null,
            'payment_code' => null,
            'status' => DepositStatus::Pending,
            'expires_at' => now()->addHours(3),
            'paid_at' => null,
            'metadata' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => DepositStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
