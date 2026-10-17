<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\MidtransService;
use App\Support\OrderFulfillment;
use App\Support\OrderTarget;
use App\Support\PaymentInstruction;
use App\Support\PhoneOperator;
use App\Support\TransactionLogger;
use App\Support\Wallet as WalletSupport;
use App\Support\WalletLedger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Request $request, Product $product): View
    {
        $this->ensurePurchasable($product);

        return view('user.checkout.show', [
            'product' => $product->load(['brand', 'category']),
            'targetLabel' => OrderTarget::label($product->category?->slug),
            'targetPlaceholder' => OrderTarget::placeholder($product->category?->slug),
            'requiresZone' => OrderTarget::requiresZone($product->brand?->slug),
            'target' => (string) $request->query('target', ''),
            'zone' => (string) $request->query('zone', ''),
            'balance' => (int) $request->user()->balance,
            'adminFee' => WalletSupport::ADMIN_FEE,
            'paymentMethods' => PaymentMethod::cases(),
            'channels' => PaymentMethod::channelOptions(),
            'selectedMethod' => old('payment_method', PaymentMethod::Saldo->value),
        ]);
    }

    public function store(Request $request, Product $product, MidtransService $midtrans): RedirectResponse
    {
        $this->ensurePurchasable($product);

        $validated = $request->validate([
            'target' => ['required', 'string', 'min:3', 'max:64'],
            'zone' => [OrderTarget::requiresZone($product->brand?->slug) ? 'required' : 'nullable', 'string', 'max:20'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_channel' => ['nullable', 'string', Rule::in(array_keys(PaymentMethod::channelOptions()))],
        ]);

        $this->ensureOperatorMatches($product, (string) $validated['target']);

        $method = PaymentMethod::from($validated['payment_method']);
        $channel = PaymentInstruction::channel($method, $validated['payment_channel'] ?? null);
        $amount = $product->selling_price;

        try {
            $order = DB::transaction(function () use ($request, $product, $validated, $amount, $method, $channel): Order {
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

                $paid = $method->isInstant();
                $provider = $item->provider;
                $orderNumber = $this->generateOrderNumber();

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
                    'status' => $status,
                    'provider' => $provider,
                    'provider_ref' => $provider !== null ? $orderNumber : null,
                    'payment_method' => $method,
                    'payment_channel' => $channel,
                    'payment_code' => $paid ? null : PaymentInstruction::code($method),
                    'paid_at' => $paid ? now() : null,
                    'expires_at' => ! $paid && $method === PaymentMethod::Qris ? now()->addHours(DepositController::EXPIRY_HOURS) : null,
                ]);

                if ($paid) {
                    WalletLedger::debit($account, $amount, WalletTransactionType::Purchase, $order, 'Pembelian '.$item->name);
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
                        'payment_channel' => $channel,
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

        if ($method === PaymentMethod::Qris) {
            try {
                $midtrans->chargeOrderQris($order);
            } catch (Throwable $e) {
                TransactionLogger::error('order.failed', 'Gagal menginisiasi pembayaran QRIS untuk pesanan.', $e, [
                    'order_number' => $order->order_number,
                    'amount' => $order->amount,
                ], $order, $request->user());
            }
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', $method->isInstant()
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
     * Untuk produk pulsa/paket data, operator nomor tujuan wajib sama dengan
     * brand produk. Mencegah user membeli paket Telkomsel untuk nomor Indosat.
     */
    private function ensureOperatorMatches(Product $product, string $target): void
    {
        $categorySlug = (string) $product->category?->slug;

        if (! in_array($categorySlug, Product::OPERATOR_CATEGORY_SLUGS, true)) {
            return;
        }

        $detected = PhoneOperator::detect($target);
        $productBrandSlug = (string) $product->brand?->slug;

        if ($detected !== null && $productBrandSlug !== '' && $detected['brand_slug'] !== $productBrandSlug) {
            throw ValidationException::withMessages([
                'target' => 'Nomor ini terdeteksi operator '.$detected['operator'].'. Silakan pilih produk yang sesuai.',
            ]);
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
