<?php

namespace App\Support\Report;

use App\Enums\OrderStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan pelanggan (CRM): aktivitas transaksi per pengguna beserta
 * segmen retensi — Baru, Aktif, Berisiko Churn, Tidak Aktif.
 */
final class CustomerReport
{
    private const ACTIVE_DAYS = 30;

    private const CHURN_DAYS = 90;

    /**
     * @param  array{segment?: string, q?: string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    /**
     * @return array{
     *     cards: array<int, array{label: string, value: string, sub: string, icon: string, tone: string}>,
     *     sections: array<int, array{title: string, note: string, columns: array<int, array{label: string, align?: string, type?: string}>, rows: array<int, array<int, scalar|null>>}>,
     *     paginator: LengthAwarePaginator,
     * }
     */
    public function build(): array
    {
        $stats = $this->aggregate();

        $segments = $stats->countBy(fn (array $row): string => $row['segment']);

        $filtered = $this->applyFilters($stats);

        $page = max(1, (int) ($this->filters['page'] ?? 1));

        $paginator = new LengthAwarePaginator(
            $this->rows($filtered->forPage($page, 15)),
            $filtered->count(),
            15,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'cards' => [
                ['label' => 'Pelanggan Bertransaksi', 'value' => number_format($stats->count(), 0, ',', '.'), 'sub' => 'Pernah memiliki pesanan', 'icon' => 'group', 'tone' => 'bg-sky-l tx-navy'],
                ['label' => 'Aktif '.self::ACTIVE_DAYS.' Hari', 'value' => number_format($segments['Aktif'] ?? 0, 0, ',', '.'), 'sub' => 'Transaksi terakhir ≤ '.self::ACTIVE_DAYS.' hari', 'icon' => 'bolt', 'tone' => 'bg-mint-l tx-mintt'],
                ['label' => 'Berisiko Churn', 'value' => number_format($segments['Berisiko Churn'] ?? 0, 0, ',', '.'), 'sub' => 'Pelanggan lama yang mulai jarang', 'icon' => 'person_off', 'tone' => 'bg-gold tx-gold'],
                ['label' => 'Baru '.self::ACTIVE_DAYS.' Hari', 'value' => number_format($segments['Baru'] ?? 0, 0, ',', '.'), 'sub' => 'Transaksi pertama ≤ '.self::ACTIVE_DAYS.' hari', 'icon' => 'person_add', 'tone' => 'bg-tealc-m tx-tealt'],
            ],
            'sections' => [
                [
                    'title' => 'Top Spender (Sepanjang Masa)',
                    'note' => 'Pelanggan dengan belanja sukses terbesar — target program loyalitas & upsell.',
                    'columns' => $this->columns(),
                    'rows' => $this->rows($filtered->take(10)),
                ],
                [
                    'title' => 'Aktivitas Transaksi Pelanggan',
                    'note' => 'Segmen: Baru (transaksi pertama ≤ '.self::ACTIVE_DAYS.' hari) · Aktif (terakhir ≤ '.self::ACTIVE_DAYS.' hari) · Berisiko Churn (terakhir '.(self::ACTIVE_DAYS + 1).'–'.self::CHURN_DAYS.' hari & ≥2 sukses) · selebihnya Tidak Aktif.',
                    'columns' => $this->columns(),
                    'rows' => $this->rows($filtered),
                ],
            ],
            'paginator' => $paginator,
        ];
    }

    /**
     * @return Collection<int, array{user: User, total: int, success: int, failed: int, spend: int, average: int, last_at: CarbonImmutable|null, segment: string}>
     */
    private function aggregate(): Collection
    {
        $stats = User::query()
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->selectRaw('users.id as user_id, COUNT(*) as total, SUM(CASE WHEN orders.status = ? THEN 1 ELSE 0 END) as success, SUM(CASE WHEN orders.status = ? THEN 1 ELSE 0 END) as failed, SUM(CASE WHEN orders.status = ? THEN orders.amount ELSE 0 END) as spend, MAX(orders.created_at) as last_at, MIN(orders.created_at) as first_at', [
                OrderStatus::Success->value,
                OrderStatus::Failed->value,
                OrderStatus::Success->value,
            ])
            ->groupBy('users.id')
            ->get();

        $users = User::query()->whereIn('id', $stats->pluck('user_id'))->get()->keyBy('id');

        return $stats
            ->map(function (object $row) use ($users): ?array {
                $user = $users->get((int) $row->user_id);

                if ($user === null) {
                    return null;
                }

                $success = (int) $row->success;
                $spend = (int) $row->spend;
                $lastAt = $row->last_at !== null ? Carbon::parse($row->last_at) : null;
                $firstAt = $row->first_at !== null ? Carbon::parse($row->first_at) : null;

                return [
                    'user' => $user,
                    'total' => (int) $row->total,
                    'success' => $success,
                    'failed' => (int) $row->failed,
                    'spend' => $spend,
                    'average' => $success > 0 ? (int) round($spend / $success) : 0,
                    'last_at' => $lastAt,
                    'segment' => $this->segment($firstAt, $lastAt, $success),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $row): int => $row['spend'])
            ->values();
    }

    private function segment($firstAt, $lastAt, int $success): string
    {
        if ($firstAt === null || $lastAt === null) {
            return 'Tidak Aktif';
        }

        $now = now();

        if ($firstAt->diffInDays($now) <= self::ACTIVE_DAYS) {
            return 'Baru';
        }

        $idle = $lastAt->diffInDays($now);

        if ($idle <= self::ACTIVE_DAYS) {
            return 'Aktif';
        }

        if ($idle <= self::CHURN_DAYS && $success >= 2) {
            return 'Berisiko Churn';
        }

        return 'Tidak Aktif';
    }

    /**
     * @param  Collection<int, array{user: User, total: int, success: int, failed: int, spend: int, average: int, last_at: CarbonImmutable|null, segment: string}>  $stats
     * @return Collection<int, array{user: User, total: int, success: int, failed: int, spend: int, average: int, last_at: CarbonImmutable|null, segment: string}>
     */
    private function applyFilters(Collection $stats): Collection
    {
        $segment = (string) ($this->filters['segment'] ?? '');

        if ($segment !== '') {
            $stats = $stats->filter(fn (array $row): bool => $row['segment'] === $segment);
        }

        $term = trim((string) ($this->filters['q'] ?? ''));

        if ($term !== '') {
            $stats = $stats->filter(fn (array $row): bool => str_contains(mb_strtolower($row['user']->name), mb_strtolower($term))
                || str_contains(mb_strtolower($row['user']->email), mb_strtolower($term)));
        }

        return $stats->values();
    }

    /**
     * @return array<int, array{label: string, align?: string, type?: string}>
     */
    private function columns(): array
    {
        return [
            ['label' => 'Nama', 'type' => 'text'],
            ['label' => 'Email', 'type' => 'text'],
            ['label' => 'Total Trx', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Sukses', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Gagal', 'align' => 'r', 'type' => 'int'],
            ['label' => 'Belanja', 'align' => 'r', 'type' => 'currency'],
            ['label' => 'Rata-rata', 'align' => 'r', 'type' => 'currency'],
            ['label' => 'Transaksi Terakhir', 'type' => 'text'],
            ['label' => 'Segmen', 'type' => 'text'],
        ];
    }

    /**
     * @param  Collection<int, array{user: User, total: int, success: int, failed: int, spend: int, average: int, last_at: CarbonImmutable|null, segment: string}>  $rows
     * @return array<int, array<int, scalar|null>>
     */
    private function rows($rows): array
    {
        return $rows->map(fn (array $row): array => [
            $row['user']->name,
            $row['user']->email,
            $row['total'],
            $row['success'],
            $row['failed'],
            $row['spend'],
            $row['average'],
            $row['last_at']?->toDateString() ?? '-',
            $row['segment'],
        ])->all();
    }
}
