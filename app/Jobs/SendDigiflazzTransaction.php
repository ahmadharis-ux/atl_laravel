<?php

namespace App\Jobs;

use App\Models\Order;
use App\Support\OrderFulfillment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Mengirim pesanan yang sudah lunas ke API transaksi Digiflazz.
 *
 * Pengiriman ulang aman karena Digiflazz menganggap `ref_id` yang sama
 * sebagai transaksi yang sama (idempoten), sehingga retry tidak akan
 * membuat transaksi ganda.
 */
class SendDigiflazzTransaction implements ShouldQueue
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

        if ($order === null) {
            return;
        }

        OrderFulfillment::send($order);
    }

    public function failed(?Throwable $e): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null) {
            return;
        }

        OrderFulfillment::fail($order, 'Gagal menghubungi Digiflazz: '.($e?->getMessage() ?? 'unknown'));
    }
}
