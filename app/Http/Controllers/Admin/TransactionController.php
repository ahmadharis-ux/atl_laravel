<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderFulfillment;
use App\Support\OrderPaymentSettler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View|JsonResponse
    {
        $orders = Order::query()
            ->with(['user', 'product.brand'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('target', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->query('payment_method')))
            ->latest('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $data = [
            'orders' => $orders,
            'listRoute' => route('admin.transactions.index'),
            'statusOptions' => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])->all(),
            'methodOptions' => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [$m->value => $m->label()])->all(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.transaction._rows', $data)->render(),
                'footer' => view('admin.transaction._footer', $data)->render(),
            ]);
        }

        return view('admin.transaction.index', $data);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'product.brand',
            'product.category',
            'logs.user',
        ]);

        $logs = $order->logs->sortByDesc('id')->values();

        return view('admin.transaction.show', [
            'order' => $order,
            'logs' => $logs,
        ]);
    }

    public function invoice(Order $order): View
    {
        return view('orders.invoice', [
            'order' => $order->load(['user', 'product.brand', 'product.category']),
            'backUrl' => route('admin.transactions.show', $order),
            'pdfUrl' => route('admin.transactions.invoice.pdf', $order),
        ]);
    }

    public function invoicePdf(Order $order): Response
    {
        $order->load(['user', 'product.brand', 'product.category']);

        return Pdf::loadView('orders.invoice-pdf', ['order' => $order])
            ->setPaper('a4')
            ->download('invoice-'.$order->order_number.'.pdf');
    }

    public function refund(Order $order): RedirectResponse
    {
        $canRefund = $order->source === 'store_qris'
            ? in_array($order->status, [OrderStatus::Failed, OrderStatus::Expired], true)
                && $order->refund_status === 'manual_required'
            : $order->status === OrderStatus::Failed;

        if (! $canRefund) {
            return back()->with('error', 'Refund manual hanya tersedia untuk transaksi gagal atau refund QRIS yang memerlukan tindakan admin.');
        }

        if (! OrderFulfillment::refundManually($order)) {
            return back()->with('error', 'Refund sedang diproses otomatis atau transaksi ini sudah direfund.');
        }

        return back()->with('success', 'Refund berhasil diproses.');
    }

    /**
     * Tandai lunas pesanan VA/e-wallet manual (mirip top up: admin memverifikasi
     * transfer masuk), lalu lanjutkan fulfilment otomatis.
     */
    public function settle(Request $request, Order $order): RedirectResponse
    {
        if (! $order->isPending()) {
            return back()->with('error', 'Hanya pesanan berstatus Menunggu Pembayaran yang dapat ditandai lunas.');
        }

        if (! OrderPaymentSettler::settle($order, $request->user())) {
            return back()->with('error', 'Pesanan '.$order->order_number.' sudah lunas atau tidak dapat diproses.');
        }

        return back()->with('success', 'Pesanan '.$order->order_number.' ditandai lunas dan diproses.');
    }

    /**
     * Batalkan pesanan yang belum dibayar dan kembalikan stoknya.
     */
    public function expire(Order $order): RedirectResponse
    {
        if (! $order->isPending()) {
            return back()->with('error', 'Hanya pesanan berstatus Menunggu Pembayaran yang dapat dibatalkan.');
        }

        OrderFulfillment::expire($order);

        return back()->with('success', 'Pesanan '.$order->order_number.' dibatalkan dan stok dikembalikan.');
    }
}
