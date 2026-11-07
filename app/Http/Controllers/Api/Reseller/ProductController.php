<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Enums\ProductPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\OrderTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Daftar produk aktif beserta harga khusus reseller.
     *
     * Filter opsional: q (nama/kode), brand_id, category_id,
     * payment_type (prabayar|pascabayar).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', Rule::in(['prabayar', 'pascabayar'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) ($validated['q'] ?? ''))).'%';

        $products = Product::query()
            ->where('status', true)
            ->where('provider', Product::PROVIDER_DIGIFLAZZ)
            ->where('payment_type', ProductPaymentType::Prabayar->value)
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->when(($validated['q'] ?? '') !== '', fn ($query) => $query->where(function ($query) use ($term): void {
                $query->where('name', 'like', $term)->orWhere('code', 'like', $term);
            }))
            ->when(! empty($validated['brand_id']), fn ($query) => $query->where('brand_id', $validated['brand_id']))
            ->when(! empty($validated['category_id']), fn ($query) => $query->where('category_id', $validated['category_id']))
            ->when(! empty($validated['payment_type']), fn ($query) => $query->where('payment_type', $validated['payment_type']))
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? 50))
            ->withQueryString();

        return response()->json([
            'data' => collect($products->items())->map(fn (Product $product): array => $this->transform($product)),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Detail satu produk aktif.
     */
    public function show(Product $product): JsonResponse
    {
        abort_unless(
            $product->status && $product->supportsResellerApiOrder(),
            404,
            'Produk tidak ditemukan atau belum didukung untuk pesanan API reseller.'
        );

        $product->load(['brand:id,name,slug', 'category:id,name,slug']);

        return response()->json([
            'data' => $this->transform($product),
        ]);
    }

    /**
     * Daftar produk untuk klien H2H lama.
     */
    public function legacyIndex(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        try {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['prabayar', 'pascabayar'])],
                'code' => ['nullable', 'string', 'max:100'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => false,
                'message' => (string) collect($exception->errors())->flatten()->first(),
                'code' => 400,
            ], 400);
        }

        if ($validated['type'] !== ProductPaymentType::Prabayar->value) {
            return response()->json([
                'status' => false,
                'message' => 'Produk pascabayar belum didukung pada API reseller.',
                'code' => 503,
            ], 503);
        }

        $products = Product::query()
            ->where('status', true)
            ->where('provider', Product::PROVIDER_DIGIFLAZZ)
            ->where('payment_type', ProductPaymentType::Prabayar->value)
            ->when(
                filled($validated['code'] ?? null),
                fn ($query) => $query->where('code', $validated['code'])
            )
            ->with(['brand:id,name,slug,logo', 'category:id,name,slug'])
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Produk tidak ditemukan.',
                'code' => 404,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $products->map(fn (Product $product): array => [
                'code' => $product->code,
                'name' => $product->name,
                'category' => $product->category?->name,
                'type' => $product->payment_type->value,
                'provider' => $product->brand?->name,
                'brand_status' => true,
                'price' => (int) $product->reseller_price,
                'note' => $product->description,
                'status' => $product->unlimited_stock || $product->stock > 0 ? 'available' : 'empty',
                'img_url' => $product->brand?->logoUrl(),
                'has_zone_id' => OrderTarget::requiresZone($product->brand?->slug),
            ]),
            'code' => 200,
        ]);
    }

    /**
     * Bentuk JSON produk: harga publik + harga reseller + besaran diskon.
     *
     * @return array<string, mixed>
     */
    private function transform(Product $product): array
    {
        $sellingPrice = (int) $product->selling_price;
        $resellerPrice = (int) $product->reseller_price;

        return [
            'id' => $product->getKey(),
            'code' => $product->code,
            'name' => $product->name,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'payment_type' => $product->payment_type?->value,
            'region' => $product->region,
            'selling_price' => $sellingPrice,
            'reseller_price' => $resellerPrice,
            'discount' => $sellingPrice - $resellerPrice,
            'stock' => $product->unlimited_stock ? null : (int) $product->stock,
            'unlimited_stock' => (bool) $product->unlimited_stock,
            'requires_zone' => OrderTarget::requiresZone($product->brand?->slug),
            'target_label' => OrderTarget::label($product->category?->slug),
        ];
    }
}
