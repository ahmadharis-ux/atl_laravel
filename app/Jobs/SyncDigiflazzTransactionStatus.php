<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\OrderFulfillment;
use App\Support\TransactionLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncDigiflazzTransactionStatus implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order !== null) {
            OrderFulfillment::syncStatus($order);
        }
    }

    public function failed(?Throwable $e): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null || $order->status !== OrderStatus::Processing) {
            return;
        }

        TransactionLogger::warning(
            'digiflazz.status_unknown',
            'Status transaksi belum dapat dipastikan setelah pemeriksaan ulang. Pesanan tetap diproses dan tidak direfund otomatis.',
            [
                'provider_ref' => $order->provider_ref,
                'exception' => $e !== null ? $e::class : null,
                'error' => $e?->getMessage(),
            ],
            $order,
            $order->user,
        );
    }
}
