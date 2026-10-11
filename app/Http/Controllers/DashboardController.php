<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Support\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Section "Sesuai Pencarianmu" memakai kata kunci paling sering dicari.
     * Menghapus riwayat pencarian tidak boleh menghilangkan rekomendasi,
     * jadi hasil terakhir diingat di sesi sebagai cadangan.
     */
    public function index(Request $request): View
    {
        $now = now()->locale('id');
        $authUser = auth()->user();

        $history = SearchHistory::topQueries((int) $authUser->getKey(), 3);
        $topQueries = $history !== []
            ? $history
            : (array) $request->session()->get('search.recommendations', []);

        if ($history !== []) {
            $request->session()->put('search.recommendations', $history);
        }

        return view('dashboard.index', [
            'user' => [
                'name' => $authUser->name,
                'role' => $authUser->is_admin ? 'Administrator' : 'Merchant',
            ],
            'dateLong' => $now->translatedFormat('l, d F Y'),
            'dateShort' => $now->translatedFormat('l, d F'),
            'balance' => (int) $authUser->balance,
            'points' => Wallet::POINTS,
            'searchHistory' => $history,
            'topQueries' => $topQueries,
            'recommended' => $this->recommendedFor($topQueries),
            'catalog' => $this->catalog(),
            'bestSellers' => Product::query()
                ->with(['brand', 'category'])
                ->where('status', true)
                ->forCatalog()
                ->bestSeller()
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * Produk yang cocok dengan kata kunci paling sering dicari pengguna.
     *
     * @param  list<string>  $terms
     * @return Collection<int, Product>
     */
    private function recommendedFor(array $terms): Collection
    {
        if ($terms === []) {
            return collect();
        }

        return Product::query()
            ->with(['brand', 'category'])
            ->where('status', true)
            ->forCatalog()
            ->where(function (Builder $query) use ($terms): void {
                foreach ($terms as $term) {
                    $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                    $query->orWhere('name', 'like', $like)->orWhere('code', 'like', $like);
                }
            })
            ->bestSeller()
            ->limit(6)
            ->get();
    }

    /**
     * Katalog kategori: label & ikon statis, URL-nya menunjuk ke daftar produk
     * yang sudah difilter sesuai kategori. Tab prabayar/pascabayar ikut
     * disetel mengikuti jenis produk terlaris pada kategori tersebut, supaya
     * tidak ada kategori yang malah membuka tab kosong.
     *
     * @return array<int, array{label: string, icon: string, url: string}>
     */
    private function catalog(): array
    {
        $categoryId = fn (string $slug): mixed => Category::where('slug', $slug)->value('id');

        $typeByCategory = Product::query()
            ->where('status', true)
            ->get(['category_id', 'payment_type', 'sold_count'])
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->sortByDesc('sold_count')->first()->payment_type->value);

        $items = [
            ['label' => 'Pulsa',        'icon' => 'smartphone',    'slug' => 'pulsa'],
            ['label' => 'Paket Data',   'icon' => 'link',          'slug' => 'data'],
            ['label' => 'Token PLN',    'icon' => 'light_mode',    'slug' => 'tagihan'],
            ['label' => 'Voucher Game', 'icon' => 'star',          'slug' => 'games'],
            ['label' => 'E-Wallet',     'icon' => 'credit_card',   'slug' => 'e-wallet'],
            ['label' => 'Akun Premium', 'icon' => 'verified_user', 'slug' => 'hiburan'],
        ];

        return array_map(function (array $item) use ($categoryId, $typeByCategory): array {
            // Pulsa & paket data punya alur sendiri (deteksi operator dulu),
            // jadi diarahkan ke halaman khusus, bukan daftar produk umum.
            if (in_array($item['slug'], ['pulsa', 'data'], true)) {
                return [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => route('pulsa.index', ['tab' => $item['slug']]),
                ];
            }

            $id = (int) $categoryId($item['slug']);

            $params = [];
            if ($id) {
                $params['category'] = $id;

                if ($type = $typeByCategory->get($id)) {
                    $params['payment_type'] = $type;
                }
            }

            return [
                'label' => $item['label'],
                'icon' => $item['icon'],
                'url' => route('products.index', $params),
            ];
        }, $items);
    }
}
