<?php

namespace App\Support;

use App\Models\Order;
use App\Notifications\TransactionResult;

/**
 * Membangun & mengirim notifikasi hasil transaksi produk ke user pemilik order.
 *
 * Dipakai bersama oleh OrderPaymentSettler (pesanan non-provider yang langsung
 * sukses) dan OrderFulfillment (pesanan ber-provider, sukses/gagal dari provider).
 */
final class OrderNotifier
{
    public static function success(Order $order): void
    {
        $user = $order->user()->first();

        if ($user === null) {
            return;
        }

        $user->notify(new TransactionResult(
            title: 'Transaksi berhasil',
            body: ($order->product?->name ?? 'Pesanan').' '.$order->order_number.' berhasil diproses.',
            level: 'success',
            icon: 'check_circle',
            url: route('orders.show', $order),
            context: self::orderContext($order),
        ));
    }

    public static function failed(Order $order, string $message): void
    {
        $user = $order->user()->first();

        if ($user === null) {
            return;
        }

        $user->notify(new TransactionResult(
            title: 'Transaksi gagal',
            body: 'Pesanan '.$order->order_number.' gagal diproses. '.$message,
            level: 'error',
            icon: 'error',
            url: route('orders.show', $order),
            context: self::orderContext($order),
        ));
    }

    /**
     * Konteks notifikasi pesanan. Menyimpan data produk & tujuan asal supaya
     * pusat notifikasi bisa menawarkan tombol "Beli Lagi" (order produk sama).
     *
     * @return array<string, mixed>
     */
    private static function orderContext(Order $order): array
    {
        return [
            'type' => 'order',
            'order_number' => $order->order_number,
            'product_id' => $order->product?->getKey(),
            'target' => $order->target,
            'target_zone' => $order->target_zone,
        ];
    }
}
