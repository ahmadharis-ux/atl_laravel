<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductPaymentType;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderFulfillment;
use App\Support\OrderNumber;
use App\Support\OrderTarget;
use App\Support\TransactionLogger;
use App\Support\WalletLedger;
use Illuminate\Database\UniqueConstraintViolationException;
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
        $idempotencyKey = $request->input('reff_id');

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $existing = $this->findByIdempotencyKey($request->user()->getKey(), $idempotencyKey);

            if ($existing !== null) {
                return $this->replayResponse($existing);
            }
        }

        $validated = $request->validate([
            'product_id' => [
                'required_without:code', 'nullable', 'integer',
                Rule::exists('products', 'id')->where('status', true),
            ],
            'code' => [
                'required_without:product_id', 'nullable', 'string', 'max:100',
                Rule::exists('products', 'code')->where('status', true),
            ],
            'target' => ['required', 'string', 'min:3', 'max:150'],
            'reff_id' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
            'limit_price' => ['nullable', 'integer', 'min:0'],
        ], [
            'product_id.exists' => 'Produk tidak tersedia atau tidak ditemukan.',
            'code.exists' => 'Produk tidak tersedia atau tidak ditemukan.',
            'reff_id.regex' => 'reff_id hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda minus.',
        ]);

        $productQuery = Product::query()
            ->with(['brand:id,name,slug', 'category:id,name,slug']);
        $product = isset($validated['product_id'])
            ? $productQuery->findOrFail($validated['product_id'])
            : $productQuery->where('code', $validated['code'])->firstOrFail();

        if (isset($validated['code']) && $product->code !== $validated['code']) {
            throw ValidationException::withMessages([
                'code' => 'Kode produk tidak cocok dengan product_id.',
            ]);
        }

        if (! $product->supportsResellerApiOrder()) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk ini belum didukung untuk pesanan API reseller.',
            ]);
        }

        $validated['product_id'] = $product->getKey();

        if (OrderTarget::requiresZone($product->brand?->slug)
            && ! filled($request->input('zone'))
            && str_contains($validated['target'], '|')) {
            $targetParts = explode('|', $validated['target'], 2);

            if (count($targetParts) !== 2 || trim($targetParts[0]) === '' || trim($targetParts[1]) === '') {
                throw ValidationException::withMessages([
                    'target' => 'Format target zona harus berupa userId|zoneId.',
                ]);
            }

            $request->merge([
                'target' => trim($targetParts[0]),
                'zone' => trim($targetParts[1]),
            ]);
            $validated['target'] = trim($targetParts[0]);
        }

        if (str_contains($validated['target'], '|')) {
            throw ValidationException::withMessages([
                'target' => 'Pisahkan target dan zona dengan field zone atau format userId|zoneId.',
            ]);
        }

        $validated += $request->validate([
            'zone' => [OrderTarget::requiresZone($product->brand?->slug) ? 'required' : 'nullable', 'string', 'max:20'],
        ]);

        OrderTarget::ensureOperatorMatches($product, (string) $validated['target']);

        $amount = (int) $product->reseller_price;

        if (isset($validated['limit_price']) && $amount > (int) $validated['limit_price']) {
            throw ValidationException::withMessages([
                'limit_price' => 'Harga produk ('.$amount.') melebihi limit_price yang dikirim.',
            ]);
        }

        try {
            $order = DB::transaction(function () use ($request, $product, $validated): Order {
                $account = $request->user()->newQuery()
                    ->whereKey($request->user()->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $item = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

                if (! $item->status || ! $item->supportsResellerApiOrder()) {
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
                $orderAmount = (int) $item->reseller_price;

                if (isset($validated['limit_price']) && $orderAmount > (int) $validated['limit_price']) {
                    throw ValidationException::withMessages([
                        'limit_price' => 'Harga produk ('.$orderAmount.') melebihi limit_price yang dikirim.',
                    ]);
                }

                $order = Order::create([
                    'user_id' => $account->getKey(),
                    'product_id' => $item->getKey(),
                    'order_number' => $orderNumber,
                    'idempotency_key' => $validated['reff_id'] ?? null,
                    'target' => $validated['target'],
                    'target_zone' => $validated['zone'] ?? null,
                    'amount' => $orderAmount,
                    'fee' => 0,
                    'supplier_cost_snapshot' => (int) $item->cost_price,
                    'company_cost_snapshot' => 0,
                    'reward_eligible' => true,
                    'status' => OrderStatus::Processing,
                    'provider' => $provider,
                    'provider_ref' => $provider !== null ? $orderNumber : null,
                    'payment_method' => PaymentMethod::Saldo,
                    'paid_at' => now(),
                    'source' => 'api',
                ]);

                WalletLedger::debit($account, $orderAmount, WalletTransactionType::Purchase, $order, 'Pembelian (reseller) '.$item->name);

                TransactionLogger::info('order.created', 'Pesanan API reseller dibuat.', [
                    'order_number' => $order->order_number,
                    'amount' => $orderAmount,
                    'source' => 'api',
                    'status' => $order->status->value,
                ], $order, $account);

                TransactionLogger::info('order.paid', 'Pembayaran saldo berhasil.', [
                    'amount' => $orderAmount,
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
        } catch (UniqueConstraintViolationException $e) {
            $existing = filled($validated['reff_id'] ?? null)
                ? $this->findByIdempotencyKey($request->user()->getKey(), (string) $validated['reff_id'])
                : null;

            if ($existing !== null) {
                return $this->replayResponse($existing);
            }

            throw $e;
        } catch (Throwable $e) {
            TransactionLogger::error('order.failed', 'Terjadi kesalahan saat memproses pesanan API reseller.', $e, [
                'product_id' => $validated['product_id'],
                'target' => $validated['target'],
                'amount' => $amount,
            ], null, $request->user());

            throw $e;
        }

        OrderFulfillment::start($order);

        $order->load(['product:id,name,code', 'product.brand:id,name,slug', 'product.category:id,name,slug']);

        return response()->json([
            'data' => $this->transform($order),
            'message' => 'Pesanan berhasil dibuat.',
        ], 201);
    }

    /**
     * Adapter untuk endpoint transaksi H2H lama.
     */
    public function legacyStore(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        try {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:100'],
                'reff_id' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
                'target' => ['required', 'string', 'max:150'],
                'limit_price' => ['nullable', 'integer', 'min:0'],
            ]);

            $existing = $this->findByIdempotencyKey(
                (int) $request->user()->getKey(),
                $validated['reff_id'],
            );

            if ($existing !== null) {
                return response()->json([
                    'status' => true,
                    'message' => 'data retrieved successfully',
                    'data' => $this->legacyTransform($this->transform($existing)),
                    'code' => 200,
                ]);
            }

            $product = Product::query()
                ->where('code', $validated['code'])
                ->where('status', true)
                ->first();

            if ($product === null || ! $product->supportsResellerApiOrder()) {
                return $this->legacyError('Produk tidak tersedia atau belum didukung.', 503);
            }

            $request->merge(['product_id' => $product->getKey()]);
            $response = $this->store($request);
            $data = $response->getData(true)['data'];

            return response()->json([
                'status' => true,
                'message' => $response->getData(true)['message'],
                'data' => $this->legacyTransform($data),
                'code' => 200,
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $message = (string) collect($errors)->flatten()->first();
            $status = isset($errors['product_id']) ? 503 : 400;

            return $this->legacyError($message, $status);
        }
    }

    /**
     * Adapter untuk polling status transaksi H2H lama.
     */
    public function legacyStatus(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        try {
            $validated = $request->validate([
                'id' => ['required', 'string', 'max:100'],
                'type' => ['required', Rule::in([ProductPaymentType::Prabayar->value])],
            ]);
        } catch (ValidationException $exception) {
            return $this->legacyError((string) collect($exception->errors())->flatten()->first(), 400);
        }

        $order = Order::query()
            ->where('user_id', $request->user()->getKey())
            ->where('source', 'api')
            ->where('order_number', $validated['id'])
            ->with(['product.brand', 'product.category'])
            ->first();

        if ($order === null) {
            return $this->legacyError('ID Transaksi tidak ditemukan.', 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->legacyTransform($this->transform($order)),
            'code' => 200,
        ]);
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
            'reff_id' => $order->idempotency_key,
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
            'serial_number' => $order->provider_sn,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }

    private function findByIdempotencyKey(int $userId, string $key): ?Order
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $key)
            ->with(['product:id,name,code', 'product.brand:id,name,slug', 'product.category:id,name,slug'])
            ->first();
    }

    private function replayResponse(Order $order): JsonResponse
    {
        $order->loadMissing(['product:id,name,code', 'product.brand:id,name,slug', 'product.category:id,name,slug']);

        return response()->json([
            'data' => $this->transform($order),
            'message' => 'Pesanan dengan reff_id ini sudah pernah diproses.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function legacyTransform(array $data): array
    {
        $target = (string) $data['target'];
        if (filled($data['target_zone'] ?? null)) {
            $target .= '|'.$data['target_zone'];
        }

        return [
            'id' => $data['order_number'],
            'reff_id' => $data['reff_id'],
            'layanan' => $data['product']['name'],
            'code' => $data['product']['code'],
            'target' => $target,
            'price' => $data['amount'],
            'sn' => $data['serial_number'],
            'status' => $data['status'],
            'created_at' => $data['created_at'],
        ];
    }

    private function legacyError(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'code' => $status,
        ], $status);
    }
}
