<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransService;
use App\Support\OrderPaymentSettler;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Request $request, Order $order): View|JsonResponse
    {
        abort_unless($order->user_id === $request->user()->getKey(), 403);

        if ($request->ajax()) {
            return response()->json([
                'active' => in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing], true),
                'status' => $order->status->value,
            ]);
        }

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

        if ($order->isExpired()) {
            $order->update(['status' => OrderStatus::Expired]);

            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Kode pembayaran sudah kedaluwarsa. Silakan buat pesanan baru.');
        }

        try {
            OrderPaymentSettler::settle($order, $request->user());
        } catch (Throwable $e) {
            TransactionLogger::error('order.failed', 'Konfirmasi pembayaran gagal.', $e, [
                'order_number' => $order->order_number,
            ], $order, $request->user());

            throw $e;
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Pembayaran pesanan '.$order->order_number.' berhasil dikonfirmasi.');
    }

    /**
     * Tarik status pembayaran QRIS terkini dari Midtrans (khusus lingkungan
     * pengembangan, karena webhook tidak menjangkau server lokal).
     */
    public function sync(Request $request, Order $order, MidtransService $midtrans): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->getKey(), 403);
        abort_if(config('midtrans.is_production'), 404);

        if ($order->payment_method !== PaymentMethod::Qris) {
            return redirect()->route('orders.show', $order);
        }

        try {
            $status = $midtrans->syncOrderStatus($order);
        } catch (Throwable $e) {
            TransactionLogger::error('order.failed', 'Sinkronisasi status Midtrans gagal.', $e, [
                'order_number' => $order->order_number,
            ], $order, $request->user());

            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Gagal mengambil status dari Midtrans.');
        }

        $message = match ($status) {
            null => 'Status tidak dapat diambil dari Midtrans.',
            'settlement', 'capture' => 'Pembayaran '.$order->order_number.' terverifikasi. Pesanan sedang diproses.',
            'expire' => 'Kode QRIS pesanan '.$order->order_number.' sudah kedaluwarsa.',
            'deny', 'cancel', 'failure' => 'Pembayaran pesanan '.$order->order_number.' ditolak atau dibatalkan.',
            default => 'Status pembayaran '.$order->order_number.': '.$status.'.',
        };

        return redirect()->route('orders.show', $order)->with('status', $message);
    }
}
