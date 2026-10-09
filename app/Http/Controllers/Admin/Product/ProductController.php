<?php

namespace App\Http\Controllers\Admin\Product;

use App\Enums\MarkupType;
use App\Enums\ProductPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function prabayar(Request $request): View|JsonResponse
    {
        return $this->listing($request, ProductPaymentType::Prabayar);
    }

    public function pascabayar(Request $request): View|JsonResponse
    {
        return $this->listing($request, ProductPaymentType::Pascabayar);
    }

    public function show(Product $product): View
    {
        $product->load(['brand', 'category', 'productType', 'seller']);

        return view('admin.product.show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): View
    {
        $product->load(['brand', 'category', 'productType', 'seller']);

        return view('admin.product.form', [
            'product' => $product,
            'markupTypes' => MarkupType::options(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'markup_type' => ['required', Rule::enum(MarkupType::class)],
            'markup_value' => ['required', 'numeric', 'min:0'],
            'max_price' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['boolean'],
        ]);

        $data['status'] = $request->boolean('status');

        $product->update($data);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('status', 'Produk "'.$product->name.'" berhasil diperbarui.');
    }

    private function listing(Request $request, ProductPaymentType $paymentType): View|JsonResponse
    {
        $products = Product::query()
            ->with(['brand', 'category', 'productType', 'seller'])
            ->paymentType($paymentType)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('seller_sku_code', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('brand'), fn ($query) => $query->where('brand_id', $request->integer('brand')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->boolean('status')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'products' => $products,
            'paymentType' => $paymentType,
            'listRoute' => $paymentType === ProductPaymentType::Prabayar
                ? route('admin.products.prepay.index')
                : route('admin.products.postpaid.index'),
            'statusTones' => [
                '1' => 'bg-mint-l tx-mintt',
                '0' => 'bg-errc-l tx-err',
            ],
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.product._rows', $data)->render(),
                'footer' => view('admin.product._footer', $data)->render(),
            ]);
        }

        return view('admin.product.index', $data + [
            'selectedCategory' => $request->filled('category')
                ? Category::whereKey($request->integer('category'))->value('name')
                : null,
            'selectedBrand' => $request->filled('brand')
                ? Brand::whereKey($request->integer('brand'))->value('name')
                : null,
        ]);
    }
}
