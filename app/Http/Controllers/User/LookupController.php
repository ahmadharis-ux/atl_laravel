<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Lookup sources that are safe to expose to end users.
     *
     * @var array<string, class-string<Model>>
     */
    private const SOURCES = [
        'brands' => Brand::class,
        'categories' => Category::class,
        'products' => Product::class,
    ];

    /**
     * Return a small, searchable slice of a lookup source for remote selects
     * and for the search-box suggestions (products).
     */
    public function __invoke(Request $request, string $type): JsonResponse
    {
        $model = self::SOURCES[$type] ?? abort(404);

        $term = trim((string) $request->query('q', ''));
        $limit = min(max($request->integer('limit', 20), 1), 50);
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        $query = $model::query();

        if ($type === 'products') {
            // Pulsa & paket data dipesan lewat /pulsa, bukan katalog produk.
            $query->forCatalog()
                ->where('status', true)
                ->when($term !== '', function ($query) use ($escaped): void {
                    $query->where(function ($q) use ($escaped): void {
                        $q->where('name', 'like', '%'.$escaped.'%')
                            ->orWhere('code', 'like', '%'.$escaped.'%');
                    });
                })
                ->orderByDesc('sold_count');
        } elseif ($term !== '') {
            $query->where('name', 'like', '%'.$escaped.'%');
        }

        if ($type === 'categories') {
            // Kategori pulsa/data tidak muncul sebagai filter di /produk.
            $query->whereNotIn('slug', Product::OPERATOR_CATEGORY_SLUGS);
        }

        if ($type === 'brands' && $request->filled('category')) {
            // Brand menyesuaikan kategori terpilih: hanya yang punya produk aktif di sana.
            $categoryId = $request->integer('category');
            $query->whereHas('products', function ($query) use ($categoryId): void {
                $query->where('category_id', $categoryId)->where('status', true);
            });
        }

        $rows = $query->orderBy('name')
            ->limit($limit)
            ->get($type === 'products'
                ? ['id', 'name', 'cost_price', 'markup_type', 'markup_value']
                : ['id', 'name', 'slug'])
            ->map(fn (Model $row): array => $this->toResult($type, $row));

        return response()->json(['results' => $rows]);
    }

    /**
     * @return array{value: int, text: string, url: string, hint: string|null}
     */
    private function toResult(string $type, Model $row): array
    {
        return [
            'value' => (int) $row->getKey(),
            'text' => $row->name,
            'url' => $this->resultUrl($type, $row),
            'hint' => $type === 'products'
                ? 'Rp '.number_format((int) $row->selling_price, 0, ',', '.')
                : null,
        ];
    }

    private function resultUrl(string $type, Model $row): string
    {
        return match ($type) {
            'categories' => route('products.index', ['category' => $row->getKey()]),
            'brands' => route('products.brand', $row),
            'products' => route('products.show', $row),
        };
    }
}
