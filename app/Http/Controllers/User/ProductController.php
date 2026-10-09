<?php

namespace App\Http\Controllers\User;

use App\Enums\ProductPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Support\GameNickname;
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

        $categorySlug = $categoryId !== null
            ? Category::query()->whereKey($categoryId)->value('slug')
            : null;

        // Kategori pulsa & data mengharuskan nomor HP dideteksi dulu supaya
        // paket yang tampil hanya milik operator dari nomor tersebut.
        $requiresOperator = in_array($categorySlug, ['pulsa', 'data'], true);
        $awaitingOperator = $requiresOperator && $brandId === null;

        // Saat mencari, tab prabayar/pascabayar dilewati supaya produk jenis apa
        // pun langsung muncul tanpa perlu memilih tab lebih dulu.
        $products = Product::query()
            ->with(['brand', 'category'])
            ->where('status', true)
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
            // Belum ada operator terdeteksi: jangan tampilkan paket apa pun.
            ->when($awaitingOperator, fn ($query) => $query->whereRaw('0 = 1'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $data = [
            'products' => $products,
            'paymentType' => $type,
            'searching' => $searching,
            'term' => $term,
            'searchHistory' => SearchHistory::topQueries((int) $request->user()->getKey(), 3),
            'requiresOperator' => $requiresOperator,
            'awaitingOperator' => $awaitingOperator,
            'categorySlug' => $categorySlug,
            'operatorCategoryIds' => Category::query()->whereIn('slug', ['pulsa', 'data'])->pluck('id')->all(),
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
        ]);
    }
}
