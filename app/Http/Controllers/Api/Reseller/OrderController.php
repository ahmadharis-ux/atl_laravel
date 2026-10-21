<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderFulfillment;
use App\Support\OrderNumber;
use App\Support\OrderTarget;
use App\Support\TransactionLogger;
use App\Support\WalletLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderController extends Controller
{
    /**
     * Riwayat pesanan milik reseller (web + API).
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with(['product:id,name,code', 'product.brand:id,name,slug', 'product.category:id,name,slug'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'data' => collect($orders->items())->map(fn (Order $order): array => $this->transform($order)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Detail satu pesanan milik reseller.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless(
            $order->user_id === $request->user()->getKey(),
            404,
            'Pesanan tidak ditemukan.'
        );

        $order->load(['product:id,name,code', 'product.brand:id,name,slug', 'product.category:id,name,slug']);

        return response()->json([
            'data' => $this->transform($order),
        ]);
    }

    /**
     * Buat pesanan baru dari reseller.
     *
     * Pembayaran selalu lewat saldo akun dengan harga khusus reseller
     * (Product::reseller_price). Stok dikunci & saldo didebit dalam satu
     * transaksi DB — kegagalan (stok habis / saldo kurang) melempar
     * ValidationException sehingga otomatis di-rollback & dibalas 422.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required', 'integer',
                Rule::exists('products', 'id')->where('status', true),
            ],
            'target' => ['required', 'string', 'min:3', 'max:64'],
        ], [
            'product_id.exists' => 'Produk tidak tersedia atau tidak ditemukan.',
        ]);

        $product = Product::query()
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->findOrFail($validated['product_id']);

        $validated += $request->validate([
            'zone' => [OrderTarget::requiresZone($product->brand?->slug) ? 'required' : 'nullable', 'string', 'max:20'],
        ]);

        OrderTarget::ensureOperatorMatches($product, (string) $validated['target']);

        $amount = (int) $product->reseller_price;

        try {
            $order = DB::transaction(function () use ($request, $product, $validated, $amount): Order {
                $account = $request->user()->newQuery()
                    ->whereKey($request->user()->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $item = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

                if (! $item->status) {
                    throw ValidationException::withMessages(['product_id' => 'Produk tidak tersedia.']);
                }

                if (! $item->unlimited_stock && $item->stock < 1) {
                    throw ValidationException::withMessages(['product_id' => 'Stok produk habis.']);
                }

                $provider = $item->provider;
                $orderNumber = OrderNumber::generate();

                if (! $item->unlimited_stock) {
                    $item->decrement('stock');
                }

                $item->increment('sold_count');

                $order = Order::create([
                    'user_id' => $account->getKey(),
                    'product_id' => $item->getKey(),
                    'order_number' => $orderNumber,
                    'target' => $validated['target'],
                    'target_zone' => $validated['zone'] ?? null,
                    'amount' => $amount,
                    'fee' => 0,
                    'status' => $provider !== null ? OrderStatus::Processing : OrderStatus::Success,
                    'provider' => $provider,
                    'provider_ref' => $provider !== null ? $orderNumber : null,
                    'payment_method' => PaymentMethod::Saldo,
                    'paid_at' => now(),
                    'source' => 'api',
                ]);

                WalletLedger::debit($account, $amount, WalletTransactionType::Purchase, $order, 'Pembelian (reseller) '.$item->name);

                TransactionLogger::info('order.created', 'Pesanan API reseller dibuat.', [
                    'order_number' => $order->order_number,
                    'amount' => $amount,
                    'source' => 'api',
                    'status' => $order->status->value,
                ], $order, $account);

                TransactionLogger::info('order.paid', 'Pembayaran saldo berhasil.', [
                    'amount' => $amount,
                    'balance_after' => $account->balance,
                ], $order, $account);

                return $order;
            });
        } catch (ValidationException $e) {
            TransactionLogger::warning('order.rejected', 'Checkout API ditolak: '.collect($e->errors())->flatten()->first(), [
                'product_id' => $validated['product_id'],
                'target' => $validated['target'],
                'errors' => $e->errors(),
            ], null, $request->user());

            throw $e;
        } catch (Throwable $e) {
            TransactionLogger::error('order.failed', 'Terjadi kesalahan saat memproses pesanan API reseller.', $e, [
                'product_id' => $validated['product_id'],
                'target' => $validated['target'],
                'amount' => $amount,
            ], null, $request->user());

            throw $e;
        }

        if ($order->status === OrderStatus::Processing) {
            OrderFulfillment::start($order);
        }

        $order->load(['product:id,name,code', 'product.brand:id,name,slug', 'product.category:id,name,slug']);

        return response()->json([
            'data' => $this->transform($order),
            'message' => 'Pesanan berhasil dibuat.',
        ], 201);
    }

    /**
     * Bentuk JSON pesanan untuk respons API.
     *
     * @return array<string, mixed>
     */
    private function transform(Order $order): array
    {
        $product = $order->product;

        return [
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'product' => [
                'id' => $product?->getKey(),
                'code' => $product?->code,
                'name' => $product?->name,
                'brand' => $product?->brand?->name,
                'category' => $product?->category?->name,
            ],
            'target' => $order->target,
            'target_zone' => $order->target_zone,
            'amount' => (int) $order->amount,
            'fee' => (int) $order->fee,
            'total' => $order->totalDue(),
            'payment_method' => $order->payment_method->value,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }
}
