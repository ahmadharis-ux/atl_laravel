<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with(['product.brand', 'product.category'])
            ->latest('id')
            ->paginate(10);

        return view('user.orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->getKey(), 403);

        return view('user.orders.show', [
            'order' => $order->load(['product.brand', 'product.category']),
        ]);
    }

    /**
     * Simulasi konfirmasi pembayaran untuk metode non-saldo (belum ada
     * payment gateway). Menandai pesanan pending menjadi sukses.
     */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->getKey(), 403);

        if ($order->status !== OrderStatus::Pending) {
            return redirect()->route('orders.show', $order);
        }

        try {
            DB::transaction(function () use ($request, $order): void {
                $locked = $order->newQuery()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->status !== OrderStatus::Pending) {
                    return;
                }

                $locked->update([
                    'status' => OrderStatus::Success,
                    'paid_at' => now(),
                ]);

                TransactionLogger::info('order.paid', 'Pembayaran dikonfirmasi.', [
                    'payment_method' => $locked->payment_method->value,
                    'amount' => $locked->amount,
                ], $locked, $request->user());
            });
        } catch (Throwable $e) {
            TransactionLogger::error('order.failed', 'Konfirmasi pembayaran gagal.', $e, [
                'order_number' => $order->order_number,
            ], $order, $request->user());

            throw $e;
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Pembayaran berhasil dikonfirmasi.');
    }
}
