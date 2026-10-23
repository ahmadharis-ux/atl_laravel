<?php

namespace App\Support\Report;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Laporan produk: paling laku & paling tidak laku pada periode,
 * plus kontribusi brand/kategori terhadap pendapatan (SWOT).
 */
final class ProductReport
{
    public function __construct(private readonly ReportPeriod $period) {}

    /**
     * @return array{
     *     cards: array<int, array{label: string, value: string, sub: string, icon: string, tone: string}>,
     *     sections: array<int, array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}>,
     * }
     */
    public function build(): array
    {
        $success = $this->period->apply(Order::query())
            ->where('status', OrderStatus::Success)
            ->get()
            ->load(['product:id,name,code,brand_id,category_id,cost_price', 'product.brand:id,name', 'product.category:id,name']);

        $perProduct = $success->groupBy('product_id')
            ->map(fn ($orders, $productId): array => [
                'product' => $orders->first()->product,
                'sold' => $orders->count(),
                'revenue' => (int) $orders->sum('amount'),
                'profit' => (int) $orders->sum('amount') - (int) $orders->sum(fn (Order $order): int => (int) $order->product?->cost_price),
            ]);

        $top = (clone $perProduct)
            ->sortByDesc(fn (array $row): int => $row['sold'])
            ->take(10)
            ->values();

        $activeProducts = Product::query()
            ->where('status', true)
            ->with(['brand:id,name', 'category:id,name'])
            ->get(['id', 'name', 'code', 'brand_id', 'category_id', 'cost_price']);

        $worst = $activeProducts
            ->map(fn (Product $product): array => $perProduct->get($product->getKey()) ?? [
                'product' => $product,
                'sold' => 0,
                'revenue' => 0,
                'profit' => 0,
            ])
            ->sortBy([fn (array $a, array $b): int => $a['sold'] <=> $b['sold'], fn (array $a, array $b): int => (string) $a['product']->name <=> (string) $b['product']->name])
            ->take(10)
            ->values();

        $noSales = $activeProducts
            ->filter(fn (Product $product): bool => ! $perProduct->has($product->getKey()))
            ->count();

        return [
            'cards' => [
                ['label' => 'Produk Terjual', 'value' => number_format((int) $success->count(), 0, ',', '.'), 'sub' => 'Pesanan sukses pada periode', 'icon' => 'shopping_cart', 'tone' => 'bg-mint-l tx-mintt'],
                ['label' => 'Pendapatan Produk', 'value' => 'Rp '.number_format((int) $success->sum('amount'), 0, ',', '.'), 'sub' => 'Pesanan sukses', 'icon' => 'payments', 'tone' => 'bg-gold tx-gold'],
                ['label' => 'Produk Aktif Terjual', 'value' => number_format($perProduct->count(), 0, ',', '.'), 'sub' => 'Produk berbeda dengan penjualan', 'icon' => 'inventory_2', 'tone' => 'bg-sky-l tx-navy'],
                ['label' => 'Produk Tanpa Penjualan', 'value' => number_format($noSales, 0, ',', '.'), 'sub' => 'Produk aktif, nol terjual', 'icon' => 'remove_shopping_cart', 'tone' => 'bg-errc-l tx-err'],
            ],
            'sections' => [
                $this->productSection('Paling Laku (Top 10)', 'Produk dengan transaksi sukses terbanyak pada periode — kandidat stok & promo utama.', $top),
                $this->productSection('Paling Tidak Laku', 'Produk aktif dengan transaksi tersedikit (termasuk nol) — kandidat review harga, bundling, atau penghentian.', $worst),
                $this->dimensionSection('Kontribusi Brand', 'Pangsa pendapatan per brand — kekuatan (dominasi) & kelemahan (ketergantungan) portofolio.', $success->groupBy(fn (Order $order): string => $order->product?->brand?->name ?? 'Tanpa Brand')),
                $this->dimensionSection('Kontribusi Kategori', 'Pangsa pendapatan per kategori — dasar keputusan ekspansi/realokasi katalog.', $success->groupBy(fn (Order $order): string => $order->product?->category?->name ?? 'Tanpa Kategori')),
            ],
        ];
    }

    /**
     * @param  Collection<int, array{product: Product|null, sold: int, revenue: int, profit: int}>  $rows
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function productSection(string $title, string $note, $rows): array
    {
        return [
            'title' => $title,
            'note' => $note,
            'columns' => [
                ['label' => 'Produk', 'type' => 'text'],
                ['label' => 'Brand', 'type' => 'text'],
                ['label' => 'Kategori', 'type' => 'text'],
                ['label' => 'Terjual', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pendapatan', 'align' => 'r', 'type' => 'currency'],
                ['label' => 'Laba', 'align' => 'r', 'type' => 'currency'],
            ],
            'rows' => $rows->map(fn (array $row): array => [
                trim(($row['product']->name ?? '-').' ('.($row['product']->code ?? '-').')'),
                $row['product']?->brand?->name ?? '-',
                $row['product']?->category?->name ?? '-',
                $row['sold'],
                $row['revenue'],
                $row['profit'],
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, Collection<int, Order>>  $groups
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function dimensionSection(string $title, string $note, $groups): array
    {
        $total = max(1, $groups->sum(fn ($group): int => $group->count()));

        $rows = $groups->map(fn ($group, string $name): array => [
            $name,
            $group->count(),
            (int) $group->sum('amount'),
            round(($group->count() / $total) * 100, 1),
        ])
            ->sortByDesc(fn (array $row): int => $row[1])
            ->values()
            ->all();

        return [
            'title' => $title,
            'note' => $note,
            'columns' => [
                ['label' => $title === 'Kontribusi Brand' ? 'Brand' : 'Kategori', 'type' => 'text'],
                ['label' => 'Transaksi', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pendapatan', 'align' => 'r', 'type' => 'currency'],
                ['label' => 'Pangsa', 'align' => 'r', 'type' => 'percent'],
            ],
            'rows' => $rows,
        ];
    }
}
