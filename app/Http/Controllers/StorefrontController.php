<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductPaymentType;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\OrderFulfillment;
use App\Support\OrderNumber;
use App\Support\OrderTarget;
use App\Support\TransactionLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    public function index(User $reseller): View
    {
        abort_unless($reseller->is_reseller, 404);

        $products = Product::query()
            ->where('status', true)
            ->forCatalog()
            ->paymentType(ProductPaymentType::Prabayar)
            ->where(function ($query): void {
                $query->where('unlimited_stock', true)->orWhere('stock', '>', 0);
            })
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(24);

        return view('storefront.index', compact('reseller', 'products'));
    }

    public function product(User $reseller, Product $product, MidtransService $midtrans): View
    {
        $this->ensureStorefrontProduct($reseller, $product);

        return view('storefront.product', [
            'reseller' => $reseller,
            'product' => $product->load(['brand', 'category']),
            'targetLabel' => OrderTarget::label($product->category?->slug),
            'targetPlaceholder' => OrderTarget::placeholder($product->category?->slug),
            'targetHint' => OrderTarget::hint($product->category?->slug),
            'requiresZone' => OrderTarget::requiresZone($product->brand?->slug),
            'storefrontKey' => Str::random(48),
            'qrisFee' => $midtrans->qrisFee($product->selling_price),
            'qrisAvailable' => $midtrans->isConfigured(),
        ]);
    }

    public function checkout(Request $request, User $reseller, Product $product, MidtransService $midtrans): RedirectResponse
    {
        $this->ensureStorefrontProduct($reseller, $product);

        $validated = $request->validate([
            'target' => ['required', 'string', 'min:3', 'max:64'],
            'zone' => [OrderTarget::requiresZone($product->brand?->slug) ? 'required' : 'nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:qris,cash'],
            'storefront_key' => ['required', 'string', 'size:48', 'regex:/\A[A-Za-z0-9]{48}\z/'],
        ]);

        OrderTarget::ensureOperatorMatches($product, $validated['target']);

        if ($validated['payment_method'] === 'qris' && ! $midtrans->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Pembayaran QRIS sedang tidak tersedia. Silakan pilih pembayaran tunai kepada reseller.',
            ]);
        }

        [$order, $created] = DB::transaction(function () use ($reseller, $product, $validated, $midtrans): array {
            $account = User::query()->whereKey($reseller->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller) {
                abort(404);
            }

            $existing = Order::query()->where('storefront_key', $validated['storefront_key'])->first();

            if ($existing !== null) {
                $existingStorefront = $existing->metadata['storefront'] ?? [];
                $isSameCheckout = $existing->user_id === $account->getKey()
                    && $existing->product_id === $product->getKey()
                    && $existing->target === $validated['target']
                    && $existing->target_zone === ($validated['zone'] ?? null)
                    && ($existingStorefront['payment_method'] ?? null) === $validated['payment_method'];

                if (! $isSameCheckout) {
                    throw ValidationException::withMessages([
                        'storefront_key' => 'Sesi checkout tidak cocok. Muat ulang halaman produk lalu coba kembali.',
                    ]);
                }

                return [$existing, false];
            }

            $item = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            if (! $item->status || (! $item->unlimited_stock && $item->stock < 1)) {
                throw ValidationException::withMessages([
                    'product' => 'Produk tidak tersedia atau stoknya habis.',
                ]);
            }

            $sellingPrice = (int) $item->selling_price;
            $resellerPrice = (int) $item->reseller_price;

            if ($resellerPrice > $sellingPrice) {
                throw ValidationException::withMessages([
                    'product' => 'Produk ini sedang tidak tersedia untuk toko reseller.',
                ]);
            }

            $paymentMethod = $validated['payment_method'];
            $amount = $paymentMethod === 'qris' ? $sellingPrice : $resellerPrice;
            $fee = $paymentMethod === 'qris' ? $midtrans->qrisFee($sellingPrice) : 0;
            $orderNumber = OrderNumber::generate();

            if (! $item->unlimited_stock) {
                $item->decrement('stock');
            }

            $item->increment('sold_count');

            $order = Order::query()->create([
                'user_id' => $account->getKey(),
                'product_id' => $item->getKey(),
                'order_number' => $orderNumber,
                'target' => $validated['target'],
                'target_zone' => $validated['zone'] ?? null,
                'amount' => $amount,
                'fee' => $fee,
                'status' => OrderStatus::Pending,
                'provider' => $item->provider,
                'provider_ref' => $item->provider !== null ? $orderNumber : null,
                'payment_method' => $paymentMethod === 'qris' ? PaymentMethod::Qris : PaymentMethod::Saldo,
                'paid_at' => null,
                'expires_at' => now()->addHours(3),
                'source' => $paymentMethod === 'qris' ? 'store_qris' : 'store_cash',
                'storefront_key' => $validated['storefront_key'],
                'metadata' => [
                    'storefront' => [
                        'payment_method' => $paymentMethod,
                        'selling_price' => $sellingPrice,
                        'reseller_price' => $resellerPrice,
                        'store_name' => $account->name,
                    ],
                ],
            ]);

            TransactionLogger::info('order.created', 'Pesanan toko reseller dibuat.', [
                'order_number' => $order->order_number,
                'amount' => $amount,
                'fee' => $fee,
                'source' => $order->source,
                'status' => $order->status->value,
            ], $order, $account);

            return [$order, true];
        });

        if (! $created) {
            $redirectUrl = $order->metadata['midtrans']['redirect_url'] ?? null;

            if ($order->source === 'store_qris' && $order->status === OrderStatus::Pending && filled($redirectUrl)) {
                return redirect()->away($redirectUrl);
            }

            return redirect()->to($this->statusUrl($order));
        }

        if ($order->source === 'store_qris') {
            $redirectUrl = $midtrans->chargeStorefrontOrder($order, $this->statusUrl($order));

            if ($redirectUrl === null) {
                OrderFulfillment::expire($order);

                return redirect()->route('storefront.product', [$reseller, $product])
                    ->withInput($request->only(['target', 'zone', 'payment_method']))
                    ->withErrors(['payment_method' => 'Halaman pembayaran gagal dibuat. Stok telah dilepas; silakan coba kembali.']);
            }

            return redirect()->away($redirectUrl);
        }

        return redirect()->to($this->statusUrl($order));
    }

    public function orderStatus(Order $order, string $token): View
    {
        abort_unless(in_array($order->source, ['store_qris', 'store_cash'], true), 404);
        abort_unless(is_string($order->storefront_key) && hash_equals($order->storefront_key, $token), 404);

        return view('storefront.order-status', [
            'order' => $order->load(['product', 'user']),
            'storefront' => $order->metadata['storefront'] ?? [],
            'paymentUrl' => $order->metadata['midtrans']['redirect_url'] ?? null,
            'syncUrl' => route('storefront.orders.sync', [
                'order' => $order->getKey(),
                'token' => $token,
            ]),
        ]);
    }

    public function syncOrderStatus(Order $order, string $token, MidtransService $midtrans): RedirectResponse
    {
        abort_unless($order->source === 'store_qris', 404);
        abort_unless(is_string($order->storefront_key) && hash_equals($order->storefront_key, $token), 404);

        if ($order->status === OrderStatus::Pending) {
            $midtrans->syncOrderStatus($order);
        }

        return redirect()->to($this->statusUrl($order));
    }

    private function ensureStorefrontProduct(User $reseller, Product $product): void
    {
        abort_unless($reseller->is_reseller, 404);
        abort_unless(
            $product->status
                && $product->payment_type === ProductPaymentType::Prabayar
                && ! in_array($product->category?->slug, Product::OPERATOR_CATEGORY_SLUGS, true),
            404,
        );
        abort_unless($product->unlimited_stock || $product->stock > 0, 404);
        abort_unless($product->reseller_price <= $product->selling_price, 404);
    }

    private function statusUrl(Order $order): string
    {
        return route('storefront.orders.show', [
            'order' => $order->getKey(),
            'token' => $order->storefront_key,
        ]);
    }
}
