<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\TransactionLogLevel;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now()->locale('id');

        $ordersTodayQuery = Order::query()->whereDate('created_at', today());

        $revenueToday = (clone $ordersTodayQuery)
            ->where('status', OrderStatus::Success)
            ->sum('amount');

        $monthQuery = Order::query()->where('created_at', '>=', now()->startOfMonth());

        $month = [
            'revenue' => (clone $monthQuery)->where('status', OrderStatus::Success)->sum('amount'),
            'orders' => (clone $monthQuery)->count(),
            'success' => (clone $monthQuery)->where('status', OrderStatus::Success)->count(),
            'failed' => (clone $monthQuery)->where('status', OrderStatus::Failed)->count(),
        ];

        return view('admin.dashboard', [
            'user' => ['name' => auth()->user()->name, 'role' => 'Administrator'],
            'dateLong' => $now->translatedFormat('l, d F Y'),
            'stats' => [
                ['label' => 'Total Pengguna', 'value' => User::query()->count(), 'icon' => 'group', 'tone' => 'bg-tealc-m tx-tealt'],
                ['label' => 'Transaksi Hari Ini', 'value' => (clone $ordersTodayQuery)->count(), 'icon' => 'receipt_long', 'tone' => 'bg-mint-l tx-mintt'],
                ['label' => 'Pendapatan Hari Ini', 'value' => 'Rp '.number_format($revenueToday, 0, ',', '.'), 'icon' => 'payments', 'tone' => 'bg-gold tx-gold'],
                ['label' => 'Pesanan Menunggu', 'value' => Order::query()->where('status', OrderStatus::Pending)->count(), 'icon' => 'pending_actions', 'tone' => 'bg-sky-l tx-navy'],
            ],
            'transactionChart' => $this->transactionChart(),
            'statusChart' => $this->statusChart(),
            'recentOrders' => Order::query()
                ->with(['user', 'product'])
                ->latest('id')
                ->take(6)
                ->get(),
            'bestSellers' => Product::query()
                ->with('brand')
                ->where('status', true)
                ->bestSeller()
                ->limit(5)
                ->get(),
            'recentErrors' => TransactionLog::query()
                ->where('level', TransactionLogLevel::Error)
                ->with('order')
                ->latest('id')
                ->take(5)
                ->get(),
            'month' => $month,
            'recentUsers' => User::query()->latest()->take(5)->get(),
        ]);
    }

    /**
     * Grafik jumlah transaksi & pendapatan 7 hari terakhir.
     *
     * @return array<string, mixed>
     */
    private function transactionChart(): array
    {
        $start = today()->subDays(6);

        $orders = Order::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'amount', 'status']);

        $counts = $orders->groupBy(fn (Order $order) => $order->created_at->toDateString())
            ->map->count();
        $revenue = $orders->where('status', OrderStatus::Success)
            ->groupBy(fn (Order $order) => $order->created_at->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'));

        $days = collect(range(6, 0))->map(fn (int $i) => today()->subDays($i));

        return [
            'data' => [
                'labels' => $days->map(fn ($day) => $day->locale('id')->translatedFormat('d M'))->all(),
                'datasets' => [
                    [
                        'label' => 'Transaksi',
                        'data' => $days->map(fn ($day) => $counts[$day->toDateString()] ?? 0)->all(),
                        'borderColor' => '#00696f',
                        'backgroundColor' => 'rgba(159,240,247,.35)',
                        'fill' => true,
                        'tension' => 0.35,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Pendapatan',
                        'data' => $days->map(fn ($day) => $revenue[$day->toDateString()] ?? 0)->all(),
                        'borderColor' => '#a76b00',
                        'backgroundColor' => 'rgba(167,107,0,.15)',
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

    /**
     * Komposisi status pesanan 30 hari terakhir.
     *
     * @return array<string, mixed>
     */
    private function statusChart(): array
    {
        $counts = Order::query()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $colors = [
            OrderStatus::Pending->value => '#a76b00',
            OrderStatus::Processing->value => '#0b6f76',
            OrderStatus::Success->value => '#00696f',
            OrderStatus::Failed->value => '#c0392b',
            OrderStatus::Expired->value => '#9aa6a5',
        ];

        $labels = [];
        $data = [];
        $background = [];

        foreach (OrderStatus::cases() as $status) {
            $total = (int) ($counts[$status->value] ?? 0);

            if ($total === 0) {
                continue;
            }

            $labels[] = $status->label();
            $data[] = $total;
            $background[] = $colors[$status->value];
        }

        return [
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $background,
                    'borderWidth' => 0,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['position' => 'bottom']],
                'cutout' => '62%',
            ],
        ];
    }
}
