<?php

namespace App\Support\Report;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan ringkasan: total transaksi, pendapatan, laba kotor,
 * tren harian, komposisi metode pembayaran & status, serta
 * analisis hari/jam sibuk (dasar keputusan operasional/SWOT).
 */
final class OverviewReport
{
    public function __construct(private readonly ReportPeriod $period) {}

    /**
     * @return array{
     *     cards: array<int, array{label: string, value: string, sub: string, icon: string, tone: string}>,
     *     sections: array<int, array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}>,
     *     chart: array<string, mixed>,
     * }
     */
    public function build(): array
    {
        $orders = $this->period->apply(Order::query())
            ->with('product:id,cost_price')
            ->get();

        $success = $orders->where('status', OrderStatus::Success);

        $revenue = (int) $success->sum('amount');
        $cogs = (int) $success->sum(fn (Order $order): int => (int) $order->product?->cost_price);
        $profit = $revenue - $cogs;
        $successCount = $success->count();
        $totalCount = $orders->count();

        $openCount = $orders->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])->count();

        return [
            'cards' => [
                ['label' => 'Total Transaksi', 'value' => number_format($totalCount, 0, ',', '.'), 'sub' => 'Semua status pada periode', 'icon' => 'receipt_long', 'tone' => 'bg-sky-l tx-navy'],
                ['label' => 'Pendapatan', 'value' => 'Rp '.number_format($revenue, 0, ',', '.'), 'sub' => 'Pesanan sukses', 'icon' => 'payments', 'tone' => 'bg-gold tx-gold'],
                ['label' => 'Laba Kotor', 'value' => 'Rp '.number_format($profit, 0, ',', '.'), 'sub' => 'Pendapatan − HPP', 'icon' => 'insights', 'tone' => 'bg-mint-l tx-mintt'],
                ['label' => 'Rata-rata Transaksi', 'value' => 'Rp '.number_format($successCount > 0 ? (int) round($revenue / $successCount) : 0, 0, ',', '.'), 'sub' => 'Per pesanan sukses', 'icon' => 'calculate', 'tone' => 'bg-tealc-m tx-tealt'],
                ['label' => 'Tingkat Sukses', 'value' => number_format($totalCount > 0 ? ($successCount / $totalCount) * 100 : 0, 1, ',', '.').'%', 'sub' => 'Sukses dari total', 'icon' => 'verified', 'tone' => 'bg-mint-l tx-mintt'],
                ['label' => 'Perlu Tindak Lanjut', 'value' => number_format($openCount, 0, ',', '.'), 'sub' => 'Pending / diproses', 'icon' => 'pending_actions', 'tone' => 'bg-errc-l tx-err'],
            ],
            'sections' => [
                $this->dailyTrend($orders),
                $this->paymentMix($orders),
                $this->statusMix($orders),
                $this->weekdayMix($orders),
                $this->hourlyPeak($orders),
            ],
            'chart' => $this->chart($orders),
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function dailyTrend($orders): array
    {
        $daily = $orders
            ->groupBy(fn (Order $order): string => $order->created_at->toDateString())
            ->map(fn ($group) => [
                'orders' => $group->count(),
                'revenue' => (int) $group->where('status', OrderStatus::Success)->sum('amount'),
                'profit' => (int) ($group->where('status', OrderStatus::Success)->sum('amount'))
                    - (int) $group->where('status', OrderStatus::Success)->sum(fn (Order $order): int => (int) $order->product?->cost_price),
            ])
            ->sortKeys();

        return [
            'title' => 'Tren Harian',
            'note' => 'Pendapatan & laba per hari (pesanan sukses) — dasar analisis tren pertumbuhan.',
            'columns' => [
                ['label' => 'Tanggal', 'type' => 'text'],
                ['label' => 'Transaksi', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pendapatan', 'align' => 'r', 'type' => 'currency'],
                ['label' => 'Laba', 'align' => 'r', 'type' => 'currency'],
            ],
            'rows' => $daily->map(fn (array $stats, string $date): array => [
                $date,
                $stats['orders'],
                $stats['revenue'],
                $stats['profit'],
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function paymentMix($orders): array
    {
        $total = max(1, $orders->where('status', OrderStatus::Success)->count());

        $rows = $orders->where('status', OrderStatus::Success)
            ->groupBy(fn (Order $order): string => $order->payment_method->value)
            ->map(fn ($group, string $method): array => [
                PaymentMethod::tryFrom($method)?->label() ?? ucfirst($method),
                $group->count(),
                (int) $group->sum('amount'),
                round(($group->count() / $total) * 100, 1),
            ])
            ->sortByDesc(fn (array $row) => $row[1])
            ->values()
            ->all();

        return [
            'title' => 'Metode Pembayaran',
            'note' => 'Kanal pembayaran yang dipakai pelanggan (hanya pesanan sukses) — dasar kekuatan/kelemahan kanal (SWOT).',
            'columns' => [
                ['label' => 'Metode', 'type' => 'text'],
                ['label' => 'Transaksi', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pendapatan', 'align' => 'r', 'type' => 'currency'],
                ['label' => 'Pangsa', 'align' => 'r', 'type' => 'percent'],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function statusMix($orders): array
    {
        $total = max(1, $orders->count());

        $rows = $orders->groupBy(fn (Order $order): string => $order->status->value)
            ->map(fn ($group, string $status): array => [
                OrderStatus::tryFrom($status)?->label() ?? $status,
                $group->count(),
                round(($group->count() / $total) * 100, 1),
            ])
            ->sortByDesc(fn (array $row) => $row[1])
            ->values()
            ->all();

        return [
            'title' => 'Status Pesanan',
            'note' => 'Komposisi status — mendeteksi kebocoran pada corong transaksi.',
            'columns' => [
                ['label' => 'Status', 'type' => 'text'],
                ['label' => 'Jumlah', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pangsa', 'align' => 'r', 'type' => 'percent'],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function weekdayMix($orders): array
    {
        $labels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

        $rows = $orders->groupBy(fn (Order $order): int => (int) $order->created_at->isoWeekDay())
            ->map(fn ($group, int $day): array => [$labels[$day] ?? (string) $day, $group->count(), (int) $group->where('status', OrderStatus::Success)->sum('amount')])
            ->sortKeys()
            ->values()
            ->all();

        return [
            'title' => 'Transaksi per Hari dalam Seminggu',
            'note' => 'Hari tersibuk untuk perencanaan operasional & promo (SWOT Operations).',
            'columns' => [
                ['label' => 'Hari', 'type' => 'text'],
                ['label' => 'Transaksi', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pendapatan', 'align' => 'r', 'type' => 'currency'],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}
     */
    private function hourlyPeak($orders): array
    {
        $rows = $orders->groupBy(fn (Order $order): int => (int) $order->created_at->format('G'))
            ->map(fn ($group, int $hour): array => [
                sprintf('%02d:00 - %02d:59', $hour, $hour),
                $group->count(),
                (int) $group->where('status', OrderStatus::Success)->sum('amount'),
            ])
            ->sortByDesc(fn (array $row) => $row[1])
            ->take(8)
            ->sortKeys()
            ->values()
            ->all();

        return [
            'title' => 'Jam Sibuk Transaksi',
            'note' => '8 jam dengan transaksi terbanyak — dasar jadwal maintenance & promo flash sale.',
            'columns' => [
                ['label' => 'Jam', 'type' => 'text'],
                ['label' => 'Transaksi', 'align' => 'r', 'type' => 'int'],
                ['label' => 'Pendapatan', 'align' => 'r', 'type' => 'currency'],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Konfigurasi chart garis tren harian (hanya untuk halaman, tidak diekspor).
     *
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    private function chart($orders): array
    {
        $days = collect(range(0, 29))->map(fn (int $i): string => today()->subDays(29 - $i)->toDateString());

        $byDay = $orders->groupBy(fn (Order $order): string => $order->created_at->toDateString());

        return [
            'data' => [
                'labels' => $days->map(fn (string $day): string => Carbon::parse($day)->locale('id')->translatedFormat('d M'))->all(),
                'datasets' => [
                    [
                        'label' => 'Transaksi',
                        'data' => $days->map(fn (string $day): int => $byDay->get($day)?->count() ?? 0)->all(),
                        'borderColor' => '#00696f',
                        'backgroundColor' => 'rgba(159,240,247,.35)',
                        'fill' => true,
                        'tension' => 0.35,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Pendapatan',
                        'data' => $days->map(fn (string $day): int => (int) ($byDay->get($day)?->where('status', OrderStatus::Success)->sum('amount') ?? 0))->all(),
                        'borderColor' => '#a76b00',
                        'fill' => false,
                        'tension' => 0.35,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => true]],
                'scales' => [
                    'y' => ['beginAtZero' => true, 'position' => 'left', 'ticks' => ['precision' => 0]],
                    'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
                ],
            ],
        ];
    }
}
