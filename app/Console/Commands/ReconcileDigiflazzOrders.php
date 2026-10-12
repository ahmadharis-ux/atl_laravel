<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderFulfillment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

#[Signature('digiflazz:reconcile-orders {--minutes=10 : Usia minimum (menit) order Processing untuk direkonsiliasi} {--limit=100 : Jumlah maksimum order per jalan}')]
#[Description('Kirim ulang transaksi ke Digiflazz untuk order yang tertahan di status Processing')]
class ReconcileDigiflazzOrders extends Command
{
    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, (int) $this->option('limit'));
        $threshold = Carbon::now()->subMinutes($minutes);

        $orders = Order::query()
            ->where('provider', Product::PROVIDER_DIGIFLAZZ)
            ->where('status', OrderStatus::Processing)
            ->where(function ($query) use ($threshold): void {
                $query->where('processed_at', '<=', $threshold)
                    ->orWhere(function ($query) use ($threshold): void {
                        $query->whereNull('processed_at')->where('created_at', '<=', $threshold);
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada order Processing yang perlu direkonsiliasi.');

            return self::SUCCESS;
        }

        $sent = 0;
        $finished = 0;

        foreach ($orders as $order) {
            try {
                OrderFulfillment::send($order);
                $sent++;

                if ($order->fresh()->status->isFinal()) {
                    $finished++;
                }
            } catch (Throwable $e) {
                $this->warn("Order {$order->order_number} gagal dikirim ulang: {$e->getMessage()}");
            }
        }

        $this->info("Direkonsiliasi: {$sent} dikirim, {$finished} selesai.");

        return self::SUCCESS;
    }
}
