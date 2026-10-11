<?php

namespace App\Http\Controllers\User;

use App\Enums\ProductPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Support\GameNickname;
use App\Support\OrderTarget;
use App\Support\ProductFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $rawTerm = $request->query('q');
        $term = is_string($rawTerm) ? trim($rawTerm) : '';
        $searching = $term !== '';

        // Hanya pencarian lewat kotak pencarian (bukan penyaring AJAX) yang dicatat.
        if ($searching && ! $request->ajax()) {
            SearchHistory::remember((int) $request->user()->getKey(), $term);
        }

        $type = ProductPaymentType::tryFrom((string) $request->query('payment_type'))
            ?? ProductPaymentType::Prabayar;

        $categoryId = $request->filled('category') ? $request->integer('category') : null;
        $brandId = $request->filled('brand') ? $request->integer('brand') : null;

        $minPrice = ProductFilters::priceBound($request, 'min_price');
        $maxPrice = ProductFilters::priceBound($request, 'max_price');

        // Pulsa & paket data punya alur sendiri di /pulsa (deteksi nomor),
        // jadi tidak ikut tampil di katalog produk umum ini.
        $products = Product::query()
            ->with(['brand', 'category'])
            ->where('status', true)
            ->forCatalog()
            ->when(! $searching, fn ($query) => $query->where('payment_type', $type))
            ->when($searching, function ($query) use ($term): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $query->where(function ($q) use ($like): void {
                    $q->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                });
            })
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->when($brandId !== null, fn ($query) => $query->where('brand_id', $brandId))
            ->priceBetween($minPrice, $maxPrice)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(16)
            ->withQueryString();

        $data = [
            'products' => $products,
            'paymentType' => $type,
            'searching' => $searching,
            'term' => $term,
            'searchHistory' => SearchHistory::topQueries((int) $request->user()->getKey(), 3),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('user.product._cards', $data)->render(),
                'tabs' => view('user.product._tabs', $data)->render(),
                'footer' => view('user.product._footer', $data)->render(),
            ]);
        }

        return view('user.product.index', $data + [
            'selectedCategory' => $request->filled('category')
                ? Category::whereKey($request->integer('category'))->value('name')
                : null,
            'selectedBrand' => $request->filled('brand')
                ? Brand::whereKey($request->integer('brand'))->value('name')
                : null,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status, 404);

        $product->load(['brand', 'category', 'productType']);

        $brandSlug = (string) $product->brand?->slug;

        return view('user.product.show', [
            'product' => $product,
            'canValidateNickname' => GameNickname::supports($brandSlug),
            'requiresZone' => GameNickname::requiresZone($brandSlug),
            'canCheckout' => OrderTarget::supportsCheckout($product->category?->slug),
        ]);
    }
}
