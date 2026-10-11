<?php

namespace Database\Factories;

use App\Enums\TransactionLogLevel;
use App\Models\Order;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionLog>
 */
class TransactionLogFactory extends Factory
{
    protected $model = TransactionLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'level' => TransactionLogLevel::Info,
            'event' => 'order.created',
            'message' => 'Pesanan dibuat.',
            'context' => null,
            'stack_trace' => null,
        ];
    }

    public function error(): static
    {
        return $this->state(fn (): array => [
            'level' => TransactionLogLevel::Error,
            'event' => 'order.failed',
            'message' => 'Terjadi kesalahan saat memproses pesanan.',
            'stack_trace' => "#0 /app/Http/Controllers/User/CheckoutController.php(88): App\\Support\\TransactionLogger::error()\n#1 {main}",
        ]);
    }
}
