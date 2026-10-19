<?php

namespace App\Http\Controllers\User;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaldoController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with(['product.category'])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('user.saldo.index', [
            'balance' => (int) $request->user()->balance,
            'points' => $request->user()->points,
            'tier' => $request->user()->tier(),
            'adminFee' => Wallet::ADMIN_FEE,
            'shortcuts' => [
                ['label' => 'Transfer', 'icon' => 'swap_horiz',    'tone' => 'bg-tealc-m tx-tealt', 'url' => null],
                ['label' => 'Poin',     'icon' => 'loyalty',       'tone' => 'bg-gold tx-gold',     'url' => null],
                ['label' => 'Kontak',   'icon' => 'support_agent', 'tone' => 'bg-sky-l tx-navy',    'url' => null],
                ['label' => 'Riwayat',  'icon' => 'history',       'tone' => 'bg-mint-l tx-mintt',  'url' => route('deposits.index')],
            ],
            'pendingDeposits' => $request->user()->deposits()
                ->where('status', DepositStatus::Pending)
                ->where('expires_at', '>', now())
                ->latest('id')
                ->limit(3)
                ->get(),
            'transactions' => $orders->map(fn (Order $order): array => [
                'icon' => $this->iconFor($order->product?->category?->slug),
                'title' => $order->product?->name ?? 'Produk tidak tersedia',
                'detail' => $order->order_number.' • '.$order->created_at->locale('id')->translatedFormat('d M H:i'),
                'amount' => $order->amount,
                'status' => $order->status->label(),
                'url' => route('orders.show', $order),
            ])->all(),
        ]);
    }

    /**
     * Riwayat mutasi saldo dari ledger (top up &amp; pembelian).
     */
    public function mutations(Request $request): View
    {
        $entries = $request->user()->walletTransactions()
            ->with('reference')
            ->latest('id')
            ->paginate(15);

        return view('user.saldo.mutations', [
            'balance' => (int) $request->user()->balance,
            'entries' => $entries,
        ]);
    }

    private function iconFor(?string $slug): string
    {
        return match ($slug) {
            'pulsa' => 'phone_android',
            'data' => 'wifi',
            'pln', 'tagihan', 'gas' => 'electric_bolt',
            'games' => 'sports_esports',
            default => 'shopping_bag',
        };
    }
}
