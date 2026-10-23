<?php

namespace App\Support\Report;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Laporan kesehatan provider fulfilment: provider yang sering gangguan
 * (tingkat gagal tinggi) dan yang selalu aman (nol kegagalan).
 */
final class ProviderReport
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
        $orders = $this->period->apply(Order::query())->get(['id', 'provider', 'status', 'created_at']);

        $groups = $orders->groupBy(fn (Order $order): string => $order->provider ?: 'manual')
            ->map(fn ($group, string $provider): array => $this->metrics($group, $provider));

        $disturbed = $groups
            ->filter(fn (array $row): bool => $row['failure_rate'] > 0)
            ->sortByDesc(fn (array $row): array => [$row['failure_rate'], $row['failed']])
            ->values();

        $safe = $groups
            ->filter(fn (array $row): bool => $row['failure_rate'] <= 0 && $row['success'] > 0)
            ->sortByDesc(fn (array $row): int => $row['success'])
            ->values();

        $failedTotal = $orders->where('status', OrderStatus::Failed)->count();
        $completed = $orders->where('status', OrderStatus::Success)->count()
            + $orders->where('status', OrderStatus::Failed)->count();

        return [
            'cards' => [
                ['label' => 'Provider Dipakai', 'value' => number_format($groups->count(), 0, ',', '.'), 'sub' => 'Termasuk manual (tanpa provider)', 'icon' => 'hub', 'tone' => 'bg-sky-l tx-navy'],
                ['label' => 'Pesanan Gagal', 'value' => number_format($failedTotal, 0, ',', '.'), 'sub' => 'Pada periode berjalan', 'icon' => 'error', 'tone' => 'bg-errc-l tx-err'],
                ['label' => 'Tingkat Gagal', 'value' => number_format($completed > 0 ? ($failedTotal / $completed) * 100 : 0, 1, ',', '.').'%', 'sub' => 'Gagal dari yang selesai', 'icon' => 'warning', 'tone' => 'bg-gold tx-gold'],
                ['label' => 'Provider Bermasalah', 'value' => number_format($disturbed->count(), 0, ',', '.'), 'sub' => 'Punya insiden gagal', 'icon' => 'report', 'tone' => 'bg-errc-l tx-err'],
            ],
            'sections' => [
                [
                    'title' => 'Provider Sering Gangguan',
                    'note' => 'Provider dengan tingkat kegagalan tertinggi — kandidat audit SLA / eskalasi / migrasi.',
                    'columns' => $this->columns(),
                    'rows' => $this->rows($disturbed),
                ],
                [
                    'title' => 'Provider Selalu Aman',
                    'note' => 'Provider tanpa kegagalan pada periode (minimal ada pesanan sukses) — kandidat perluasan beban.',
                    'columns' => $this->columns(),
                    'rows' => $this->rows($safe),
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, Order>  $group
     * @return array{label: string, total: int, success: int, failed: int, expired: int, open: int, failure_rate: float, last_failure: string|null}
     */
    private function metrics($group, string $provider): array
    {
        $failed = $group->where('status', OrderStatus::Failed);
        $success = $group->where('status', OrderStatus::Success);
        $denominator = $success->count() + $failed->count();

        return [
            'label' => $provider === 'manual' ? 'Manual (tanpa provider)' : $provider,
            'total' => $group->count(),
            'success' => $success->count(),
            'failed' => $failed->count(),
            'expired' => $group->where('status', OrderStatus::Expired)->count(),
            'open' => $group->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])->count(),
            'failure_rate' => $denominator > 0 ? round(($failed->count() / $denominator) * 100, 1) : 0.0,
            'last_failure' => $failed->max('created_at')?->toDateString(),
        ];
    }

    /**
     * @return array<int, array{label: string, align?: string, type?: string}>
     */
    private function columns(): array
    {
        return [
            ['label' => 'Provider', 'type' => 'text'],
            ['label' => 'Total', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Sukses', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Gagal', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Kedaluwarsa', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Berjalan', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Gagal %', 'align' => 'r', 'type' => 'percent'],
            ['label' => 'Insiden Terakhir', 'type' => 'text'],
        ];
    }

    /**
     * @param  Collection<int, array{label: string, total: int, success: int, failed: int, expired: int, open: int, failure_rate: float, last_failure: string|null}>  $rows
     * @return array<int, array<int, scalar|null>>
     */
    private function rows($rows): array
    {
        return $rows->map(fn (array $row): array => [
            $row['label'],
            $row['total'],
            $row['success'],
            $row['failed'],
            $row['expired'],
            $row['open'],
            $row['failure_rate'],
            $row['last_failure'] ?? '-',
        ])->all();
    }
}
