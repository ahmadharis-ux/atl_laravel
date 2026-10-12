<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\OrderFulfillment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('orders:expire-pending {--minutes= : Batas waktu pembayaran dalam menit (default dari config)} {--limit=200 : Jumlah maksimum order per jalan}')]
#[Description('Kedaluwarsakan pesanan yang belum dibayar dan kembalikan stoknya')]
class ExpirePendingOrders extends Command
{
    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: config('orders.pending_ttl_minutes', 180));
        $minutes = max(1, $minutes);
        $limit = max(1, (int) $this->option('limit'));

        $orders = Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('created_at', '<=', Carbon::now()->subMinutes($minutes))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada pesanan Pending yang perlu dikedaluwarsakan.');

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            OrderFulfillment::expire($order);
        }

        $this->info("Dikedaluwarsakan {$orders->count()} pesanan.");

        return self::SUCCESS;
    }
}
