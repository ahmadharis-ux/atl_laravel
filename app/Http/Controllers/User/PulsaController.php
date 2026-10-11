<?php

namespace App\Http\Controllers\User;

use App\Enums\ProductPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PulsaController extends Controller
{
    /**
     * Halaman khusus pulsa & paket data. Pengguna memasukkan nomor HP lebih
     * dulu; operator dideteksi dari nomor tersebut, lalu paket milik operator
     * itu yang ditampilkan (dipisah per tab Pulsa / Paket Data).
     */
    public function index(Request $request): View|JsonResponse
    {
        $pulsaCategoryId = (int) Category::query()->where('slug', 'pulsa')->value('id');
        $dataCategoryId = (int) Category::query()->where('slug', 'data')->value('id');

        $tab = $this->resolveTab($request, $dataCategoryId);
        $categoryId = $tab === 'data' ? $dataCategoryId : $pulsaCategoryId;

        $brandId = $request->filled('brand') ? $request->integer('brand') : null;
        $brand = $brandId !== null ? Brand::query()->find($brandId) : null;

        $minPrice = ProductFilters::priceBound($request, 'min_price');
        $maxPrice = ProductFilters::priceBound($request, 'max_price');
        $sort = ProductFilters::sort($request);

        // Belum ada operator terdeteksi: jangan tampilkan paket apa pun.
        $awaitingOperator = $brandId === null;

        $query = Product::query()
            ->with(['brand', 'category'])
            ->where('status', true)
            ->where('payment_type', ProductPaymentType::Prabayar)
            ->when($categoryId !== 0, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($brandId !== null, fn (Builder $query) => $query->where('brand_id', $brandId))
            ->when($awaitingOperator, fn (Builder $query) => $query->whereRaw('0 = 1'))
            ->priceBetween($minPrice, $maxPrice);

        ProductFilters::applyOrdering($query, $sort);

        $products = $query->paginate(12)->withQueryString();

        $data = [
            'products' => $products,
            'tab' => $tab,
            'brandId' => $brandId,
            'operatorName' => $brand?->name,
            'awaitingOperator' => $awaitingOperator,
            'filtering' => $minPrice !== null || $maxPrice !== null,
            'sort' => $sort,
            'sortOptions' => ProductFilters::sortOptions(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('user.pulsa._cards', $data)->render(),
                'tabs' => view('user.pulsa._tabs', $data)->render(),
                'footer' => view('user.product._footer', $data)->render(),
            ]);
        }

        return view('user.pulsa.index', $data);
    }

    /**
     * Tentukan tab aktif dari parameter `tab` atau dari `category` (id kategori).
     */
    private function resolveTab(Request $request, int $dataCategoryId): string
    {
        if ($request->query('tab') === 'data') {
            return 'data';
        }

        if ($request->filled('category') && $request->integer('category') === $dataCategoryId) {
            return 'data';
        }

        return 'pulsa';
    }
}
