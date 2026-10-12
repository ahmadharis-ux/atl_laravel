<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderFulfillment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
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
            ->paginate(15)
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

    public function refund(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::Failed) {
            return back()->with('error', 'Hanya transaksi gagal yang dapat direfund.');
        }

        if (! OrderFulfillment::refundManually($order)) {
            return back()->with('error', 'Transaksi ini sudah direfund sebelumnya.');
        }

        return back()->with('success', 'Refund berhasil diproses.');
    }
}
