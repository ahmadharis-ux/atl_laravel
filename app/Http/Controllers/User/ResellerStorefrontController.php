<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderStatus;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Order;
use App\Models\ResellerPayout;
use App\Models\User;
use App\Support\OrderFulfillment;
use App\Support\OrderPaymentSettler;
use App\Support\PinService;
use App\Support\WalletLedger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResellerStorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && $user->reseller_since !== null, 403);

        $pendingCashOrders = $user->orders()
            ->where('source', 'store_cash')
            ->where('status', OrderStatus::Pending)
            ->where('expires_at', '>', now())
            ->with('product')
            ->latest('id')
            ->get();

        $payouts = $user->resellerPayouts()->latest('id')->limit(10)->get();
        $commissionHistory = $user->resellerCommissions()
            ->with('order.product')
            ->latest('id')
            ->limit(10)
            ->get();

        $periodStart = now()->subDays(29)->startOfDay();
        $recentOrders = $user->orders()
            ->where('created_at', '>=', $periodStart)
            ->get(['created_at', 'amount', 'source']);
        $chartDays = collect(range(0, 29))->map(fn (int $days): string => $periodStart->copy()->addDays($days)->format('Y-m-d'));
        $dailyOrders = $recentOrders->groupBy(fn (Order $order): string => $order->created_at->format('Y-m-d'));
        $transactionChart = [
            'data' => [
                'labels' => $chartDays->map(fn (string $date): string => date('d/m', strtotime($date)))->values(),
                'datasets' => [[
                    'label' => 'Transaksi',
                    'data' => $chartDays->map(fn (string $date): int => $dailyOrders->get($date, collect())->count())->values(),
                    'borderColor' => '#1c9b91',
                    'backgroundColor' => 'rgba(28, 155, 145, .12)',
                    'fill' => true,
                    'tension' => .35,
                ]],
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            ],
        ];
        $orderStats = [
            'total' => $user->orders()->count(),
            'api' => $user->orders()->where('source', 'api')->count(),
            'hosted' => $user->orders()->whereIn('source', ['store_cash', 'store_qris'])->count(),
            'pendingCash' => $pendingCashOrders->count(),
        ];

        return view('user.settings.reseller-store', [
            'user' => $user,
            'storeUrl' => $user->is_reseller ? route('storefront.index', $user) : null,
            'pendingCashOrders' => $pendingCashOrders,
            'availableCommission' => $this->availableCommission($user),
            'bankAccounts' => $user->bankAccounts()->orderByDesc('is_primary')->orderBy('bank_name')->get(),
            'payouts' => $payouts,
            'commissionHistory' => $commissionHistory,
            'orderStats' => $orderStats,
            'transactionChart' => $transactionChart,
        ]);
    }

    public function transactions(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && $user->reseller_since !== null, 403);

        $orders = $user->orders()
            ->with(['product.brand', 'product.category'])
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->string('source')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($request->string('q')->toString())).'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('order_number', 'like', $term)
                        ->orWhere('target', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('user.settings.reseller-transactions.index', [
            'orders' => $orders,
        ]);
    }

    public function transactionShow(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->getKey(), 404);
        abort_unless($request->user()->reseller_since !== null, 403);

        return view('user.settings.reseller-transactions.show', [
            'order' => $order->load(['product.brand', 'product.category', 'walletTransactions', 'resellerCommission']),
        ]);
    }

    public function confirmCash(Request $request, Order $order, PinService $pinService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->reseller_since !== null, 403);

        $validated = $request->validate([
            'pin' => ['required', 'digits:6'],
        ], [
            'pin.required' => 'PIN transaksi wajib diisi untuk konfirmasi tunai.',
            'pin.digits' => 'PIN transaksi harus terdiri dari 6 angka.',
        ]);

        $pinResult = $pinService->verify($user, $validated['pin'], 'reseller_cash', $request);

        if (! $pinResult->isValidFor($user)) {
            throw ValidationException::withMessages(['pin' => $pinResult->message()]);
        }

        $retryAfter = $pinService->highValueRetryAfterSeconds($user, (int) $order->amount);

        if ($retryAfter !== null) {
            throw ValidationException::withMessages(['pin' => $pinService->highValueMessage($retryAfter)]);
        }

        $expired = false;
        $lockedOrder = DB::transaction(function () use ($user, $order, &$expired, $pinResult): Order {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->where('user_id', $account->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->source !== 'store_cash'
                || $locked->status !== OrderStatus::Pending
                || ($locked->metadata['storefront']['payment_method'] ?? null) !== 'cash'
                || filled($locked->metadata['storefront']['cash_confirmed_at'] ?? null)) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan tunai ini sudah diproses atau tidak dapat dikonfirmasi.',
                ]);
            }

            if ($account->reseller_since === null) {
                throw ValidationException::withMessages([
                    'order' => 'Akun reseller tidak aktif.',
                ]);
            }

            if ($locked->isExpired()) {
                $expired = true;

                return $locked;
            }

            WalletLedger::debit(
                $account,
                (int) $locked->amount,
                WalletTransactionType::Purchase,
                $locked,
                'Pembelian tunai toko reseller '.$locked->product?->name,
                ['source' => 'store_cash'],
                $pinResult,
            );

            $metadata = $locked->metadata ?? [];
            $metadata['storefront']['cash_confirmed_at'] = now()->toIso8601String();
            $locked->forceFill([
                'metadata' => $metadata,
                'expires_at' => null,
            ])->save();

            if (! OrderPaymentSettler::settle($locked, $account)) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan tidak dapat diproses. Periksa status transaksi.',
                ]);
            }

            return $locked;
        });

        if ($expired) {
            OrderFulfillment::expire($lockedOrder);

            return back()->with('error', 'Batas konfirmasi tunai sudah lewat. Pesanan kedaluwarsa dan stok dikembalikan.');
        }

        return back()->with('status', 'Penerimaan tunai dikonfirmasi dan saldo operasional dipotong.');
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        abort_unless($user !== null && $user->reseller_since !== null, 403);

        DB::transaction(function () use ($user, $validated): void {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $bankAccount = BankAccount::query()
                ->whereKey($validated['bank_account_id'])
                ->where('user_id', $account->getKey())
                ->lockForUpdate()
                ->first();

            if ($bankAccount === null) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Pilih rekening bank yang terdaftar di akunmu.',
                ]);
            }

            $available = $this->availableCommission($account);

            if ($validated['amount'] > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pencairan melebihi komisi yang tersedia.',
                ]);
            }

            ResellerPayout::query()->create([
                'user_id' => $account->getKey(),
                'bank_account_id' => $bankAccount->getKey(),
                'amount' => $validated['amount'],
                'status' => 'pending',
                'bank_name' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
                'account_holder' => $bankAccount->account_holder,
            ]);
        });

        return back()->with('status', 'Permintaan pencairan dikirim dan menunggu verifikasi admin.');
    }

    private function availableCommission(User $user): int
    {
        $earned = (int) $user->resellerCommissions()->sum('amount');
        $requested = (int) $user->resellerPayouts()
            ->whereIn('status', ['pending', 'paid'])
            ->sum('amount');

        return max(0, $earned - $requested);
    }
}
