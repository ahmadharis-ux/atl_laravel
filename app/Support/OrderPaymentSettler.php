<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menandai pesanan non-saldo (mis. QRIS) sebagai lunas setelah pembayaran
 * dikonfirmasi, lalu memulai pemenuhan bila produk ber-provider.
 *
 * Dipakai bersama oleh konfirmasi pembayaran user (mode development) dan
 * webhook Midtrans. Idempoten: pesanan yang sudah tidak pending dilewati.
 */
final class OrderPaymentSettler
{
    public static function settle(Order $order, ?User $actor = null): bool
    {
        $settled = DB::transaction(function () use ($order, $actor): bool {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status !== OrderStatus::Pending) {
                return false;
            }

            $locked->update([
                'status' => $locked->usesProviderFulfillment()
                    ? OrderStatus::Processing
                    : OrderStatus::Success,
                'paid_at' => now(),
            ]);

            TransactionLogger::info('order.paid', 'Pembayaran dikonfirmasi.', [
                'payment_method' => $locked->payment_method->value,
                'amount' => $locked->amount,
            ], $locked, $actor ?? $locked->user);

            return true;
        });

        if (! $settled) {
            return false;
        }

        $order->refresh();

        if ($order->status === OrderStatus::Success) {
            $order->user?->addPoint();
            OrderNotifier::success($order);
        }

        OrderFulfillment::start($order);

        return true;
    }
}
