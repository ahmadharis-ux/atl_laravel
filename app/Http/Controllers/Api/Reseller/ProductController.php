<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\OrderTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        abort_unless($product->status, 404, 'Produk tidak ditemukan atau tidak aktif.');

        $product->load(['brand:id,name,slug', 'category:id,name,slug']);

        return response()->json([
            'data' => $this->transform($product),
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
