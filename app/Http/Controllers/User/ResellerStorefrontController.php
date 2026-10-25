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

        return view('user.settings.reseller-store', [
            'user' => $user,
            'storeUrl' => $user->is_reseller ? route('storefront.index', $user) : null,
            'pendingCashOrders' => $pendingCashOrders,
            'availableCommission' => $this->availableCommission($user),
            'bankAccounts' => $user->bankAccounts()->orderByDesc('is_primary')->orderBy('bank_name')->get(),
            'payouts' => $payouts,
            'commissionHistory' => $commissionHistory,
        ]);
    }

    public function confirmCash(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->reseller_since !== null, 403);

        $expired = false;
        $lockedOrder = DB::transaction(function () use ($user, $order, &$expired): Order {
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
