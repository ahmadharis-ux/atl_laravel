<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use App\Models\MasterBank;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\TransactionResult;
use App\Services\MidtransService;
use App\Support\OrderFulfillment;
use App\Support\OrderNumber;
use App\Support\OrderRewardLedger;
use App\Support\OrderTarget;
use App\Support\PaymentInstruction;
use App\Support\Roles;
use App\Support\TransactionLogger;
use App\Support\WalletLedger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Request $request, Product $product, MidtransService $midtrans): View
    {
        $this->ensurePurchasable($product);
        $user = $request->user();
        $rewardTier = $user->tier();
        $paymentMethods = FeatureSetting::availablePaymentMethods('transaction');
        $paymentMethodValues = $this->paymentMethodValues($paymentMethods);
        $selectedMethod = old('payment_method', PaymentMethod::Saldo->value);

        if (! in_array($selectedMethod, $paymentMethodValues, true)) {
            $selectedMethod = $paymentMethodValues[0] ?? null;
        }

        $targets = MasterBank::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('bank_name')
            ->get();

        return view('user.checkout.show', [
            'product' => $product->load(['brand', 'category']),
            'targetLabel' => OrderTarget::label($product->category?->slug),
            'targetPlaceholder' => OrderTarget::placeholder($product->category?->slug),
            'targetHint' => OrderTarget::hint($product->category?->slug),
            'requiresZone' => OrderTarget::requiresZone($product->brand?->slug),
            'target' => (string) $request->query('target', ''),
            'zone' => (string) $request->query('zone', ''),
            'balance' => (int) $user->balance,
            'rewardEstimate' => OrderRewardLedger::estimate(
                (int) $product->selling_price,
                (int) $product->cost_price,
                0,
                $rewardTier,
            ),
            'rewardTier' => $rewardTier,
            'qrisFee' => $midtrans->qrisFee($product->selling_price),
            'paymentMethods' => $paymentMethods,
            'bankTargets' => $targets->where('type', MasterBank::TYPE_BANK)->values(),
            'ewalletTargets' => $targets->where('type', MasterBank::TYPE_EWALLET)->values(),
            'selectedMethod' => $selectedMethod,
        ]);
    }

    public function store(Request $request, Product $product, MidtransService $midtrans): RedirectResponse
    {
        $this->ensurePurchasable($product);
        $paymentMethods = FeatureSetting::availablePaymentMethods('transaction');

        $validated = $request->validate([
            'target' => ['required', 'string', 'min:3', 'max:64'],
            'zone' => [OrderTarget::requiresZone($product->brand?->slug) ? 'required' : 'nullable', 'string', 'max:20'],
            'payment_method' => ['required', Rule::in($this->paymentMethodValues($paymentMethods))],
            'master_bank_id' => ['nullable', 'integer'],
        ], [
            'payment_method.in' => 'Metode pembayaran ini sedang tidak tersedia.',
        ]);

        $user = $request->user();
        $pendingSame = Order::query()
            ->where('user_id', $user->getKey())
            ->where('product_id', $product->getKey())
            ->where('target', $validated['target'])
            ->where('target_zone', $validated['zone'] ?? null)
            ->where('payment_method', PaymentMethod::Qris)
            ->where('status', OrderStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingSame) {
            throw ValidationException::withMessages([
                'payment_method' => 'Kamu masih memiliki pesanan QRIS yang belum dibayar untuk produk & tujuan yang sama. Selesaikan pembayaran atau tunggu hingga kedaluwarsa terlebih dahulu.',
            ]);
        }

        $method = PaymentMethod::from($validated['payment_method']);
        $masterBank = $this->resolveMasterBank($method, $validated['master_bank_id'] ?? null);
        $amount = $product->selling_price;
        try {
            $order = DB::transaction(function () use ($request, $product, $validated, $amount, $method, $masterBank, $midtrans): Order {
                $account = $request->user()->newQuery()
                    ->whereKey($request->user()->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $item = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

                if (! $item->status) {
                    throw ValidationException::withMessages(['product' => 'Produk tidak tersedia.']);
                }

                if (! $item->unlimited_stock && $item->stock < 1) {
                    throw ValidationException::withMessages(['product' => 'Stok produk habis.']);
                }

                $amount = (int) $item->selling_price;
                $fee = match ($method) {
                    PaymentMethod::Qris => $midtrans->qrisFee($amount),
                    PaymentMethod::VirtualAccount, PaymentMethod::EWallet => $masterBank?->admin_fee ?? 0,
                    PaymentMethod::Saldo => 0,
                };

                $paid = $method->isInstant();
                $provider = $item->provider;
                $orderNumber = OrderNumber::generate();

                if (! $item->unlimited_stock) {
                    $item->decrement('stock');
                }

                $item->increment('sold_count');

                $status = match (true) {
                    ! $paid => OrderStatus::Pending,
                    $provider !== null => OrderStatus::Processing,
                    default => OrderStatus::Success,
                };

                $order = Order::create([
                    'user_id' => $account->getKey(),
                    'product_id' => $item->getKey(),
                    'order_number' => $orderNumber,
                    'target' => $validated['target'],
                    'target_zone' => $validated['zone'] ?? null,
                    'amount' => $amount,
                    'fee' => $fee,
                    'supplier_cost_snapshot' => (int) $item->cost_price,
                    'company_cost_snapshot' => 0,
                    'reward_eligible' => true,
                    'status' => $status,
                    'provider' => $provider,
                    'provider_ref' => $provider !== null ? $orderNumber : null,
                    'payment_method' => $method,
                    'payment_channel' => $masterBank?->bank_name,
                    'payment_code' => $paid ? null : ($masterBank?->account_number ?? PaymentInstruction::code($method)),
                    'master_bank_id' => $masterBank?->getKey(),
                    'paid_at' => $paid ? now() : null,
                    'expires_at' => ! $paid ? now()->addHours(3) : null,
                ]);

                if ($paid) {
                    WalletLedger::debit($account, $amount, WalletTransactionType::Purchase, $order, 'Pembelian '.$item->name);
                }

                if ($status === OrderStatus::Success) {
                    OrderRewardLedger::award($order);
                }

                TransactionLogger::info('order.created', 'Pesanan dibuat.', [
                    'order_number' => $order->order_number,
                    'amount' => $amount,
                    'payment_method' => $method->value,
                    'status' => $order->status->value,
                ], $order, $account);

                if ($paid) {
                    TransactionLogger::info('order.paid', 'Pembayaran saldo berhasil.', [
                        'amount' => $amount,
                        'balance_after' => $account->balance,
                    ], $order, $account);
                } else {
                    TransactionLogger::info('order.awaiting_payment', 'Menunggu pembayaran '.$method->label().'.', [
                        'payment_code' => $order->payment_code,
                        'payment_channel' => $order->payment_channel,
                        'fee' => $order->fee,
                    ], $order, $account);
                }

                return $order;
            });
        } catch (ValidationException $e) {
            TransactionLogger::warning('order.rejected', 'Checkout ditolak: '.collect($e->errors())->flatten()->first(), [
                'product_id' => $product->getKey(),
                'target' => $validated['target'],
                'payment_method' => $method->value,
                'errors' => $e->errors(),
            ], null, $request->user());

            throw $e;
        } catch (Throwable $e) {
            TransactionLogger::error('order.failed', 'Terjadi kesalahan saat memproses checkout.', $e, [
                'product_id' => $product->getKey(),
                'target' => $validated['target'],
                'payment_method' => $method->value,
                'amount' => $amount,
            ], null, $request->user());

            throw $e;
        }

        if ($order->status === OrderStatus::Processing) {
            OrderFulfillment::start($order);
        }

        $qrisIssueFailed = false;

        if ($method === PaymentMethod::Qris) {
            try {
                $qrisIssueFailed = $midtrans->chargeOrderQris($order) === null;

                if ($qrisIssueFailed) {
                    TransactionLogger::warning('order.payment_pending', 'QRIS belum berhasil ditampilkan untuk pesanan.', [
                        'order_number' => $order->order_number,
                        'amount' => $order->totalDue(),
                    ], $order, $request->user());
                }
            } catch (Throwable $e) {
                $qrisIssueFailed = true;
                TransactionLogger::error('order.failed', 'Gagal menginisiasi pembayaran QRIS untuk pesanan.', $e, [
                    'order_number' => $order->order_number,
                    'amount' => $order->amount,
                ], $order, $request->user());
            }
        }

        if ($method->requiresChannel()) {
            User::whereHas('roles', fn ($q) => $q->where('name', Roles::ADMIN))
                ->get()
                ->each(fn ($user) => $user->notify(new TransactionResult(
                    title: 'Pembayaran Pesanan Baru',
                    body: 'Pengguna '.$order->user->name.' membuat pesanan '.$order->order_number.' (Rp '.number_format($order->totalDue(), 0, ',', '.').'). Transfer '.$method->label().' ke '.$order->payment_channel.' menunggu verifikasi.',
                    level: 'info',
                    url: route('admin.transactions.show', $order),
                    context: ['type' => 'order_new', 'reference' => $order->order_number],
                )));
        }

        $redirect = redirect()->route('orders.show', $order);

        if ($qrisIssueFailed) {
            return $redirect->with('error', 'Pesanan tersimpan, tetapi QRIS belum dapat ditampilkan. Jangan membayar melalui kode lain. Tekan Cek Status pada detail pesanan atau hubungi CS sebelum mencoba lagi.');
        }

        return $redirect->with('status', $method->isInstant()
                ? 'Pembayaran berhasil. Pesanan '.$order->order_number.' sedang diproses.'
                : 'Pesanan '.$order->order_number.' dibuat. Selesaikan pembayaran untuk memproses pesanan.');
    }

    /**
     * Pulsa & paket data punya alur tersendiri (deteksi operator), jadi
     * halaman checkout generik ini belum melayaninya.
     */
    private function ensurePurchasable(Product $product): void
    {
        abort_unless($product->status, 404);
    }

    /**
     * @param  list<PaymentMethod>  $methods
     * @return list<string>
     */
    private function paymentMethodValues(array $methods): array
    {
        return array_map(fn (PaymentMethod $method): string => $method->value, $methods);
    }

    /**
     * Ambil master bank tujuan sesuai metode. Wajib untuk VA & e-wallet.
     */
    private function resolveMasterBank(PaymentMethod $method, ?int $id): ?MasterBank
    {
        if (! $method->requiresChannel()) {
            return null;
        }

        $type = $method === PaymentMethod::EWallet ? MasterBank::TYPE_EWALLET : MasterBank::TYPE_BANK;

        $masterBank = MasterBank::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->whereKey($id)
            ->first();

        if ($masterBank === null) {
            throw ValidationException::withMessages([
                'master_bank_id' => $method === PaymentMethod::EWallet
                    ? 'Pilih e-wallet tujuan yang tersedia.'
                    : 'Pilih bank tujuan yang tersedia.',
            ]);
        }

        return $masterBank;
    }
}
