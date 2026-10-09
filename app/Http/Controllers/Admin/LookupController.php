<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Whitelisted lookup sources keyed by URL segment.
     *
     * @var array<string, class-string<Model>>
     */
    private const SOURCES = [
        'brands' => Brand::class,
        'categories' => Category::class,
        'types' => Type::class,
        'sellers' => Seller::class,
        'products' => Product::class,
    ];

    /**
     * Return a small, searchable slice of a lookup source for remote selects.
     */
    public function __invoke(Request $request, string $type): JsonResponse
    {
        $model = self::SOURCES[$type] ?? abort(404);

        $term = trim((string) $request->query('q', ''));
        $limit = min(max($request->integer('limit', 20), 1), 50);
        $label = 'name';
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        $results = $model::query()
            ->when($term !== '', fn ($query) => $query->where($label, 'like', '%'.$escaped.'%'))
            ->orderBy($label)
            ->limit($limit)
            ->get(['id', $label])
            ->map(fn (Model $row): array => [
                'value' => $row->getKey(),
                'text' => $row->{$label},
            ]);

        return response()->json(['results' => $results]);
    }
}
