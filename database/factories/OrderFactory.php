<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'order_number' => 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'target' => fake()->numerify('08##########'),
            'target_zone' => null,
            'amount' => fake()->numberBetween(1000, 100000),
            'status' => OrderStatus::Processing,
            'payment_method' => PaymentMethod::Saldo,
            'payment_channel' => null,
            'payment_code' => null,
            'paid_at' => null,
            'notes' => null,
        ];
    }
}
