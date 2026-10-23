<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResellerController extends Controller
{
    /**
     * Laporan pengguna yang memilih menjadi reseller + statistiknya.
     */
    public function index(Request $request): View|JsonResponse
    {
        $items = User::query()
            ->whereNotNull('reseller_since')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_reseller', $request->query('status') === 'active'))
            ->withCount(['orders', 'orders as api_orders_count' => fn ($query) => $query->where('source', 'api')])
            ->withSum(['orders as api_amount_sum' => fn ($query) => $query->where('source', 'api')], 'amount')
            ->orderByDesc('reseller_since')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'items' => $items,
            'listRoute' => route('admin.resellers.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.reseller._rows', $data)->render(),
                'footer' => view('admin.reseller._footer', $data)->render(),
            ]);
        }

        $data['stats'] = [
            'totalResellers' => User::query()->whereNotNull('reseller_since')->count(),
            'activeResellers' => User::query()->where('is_reseller', true)->count(),
            'apiOrders' => Order::query()->where('source', 'api')->count(),
            'apiAmount' => (int) Order::query()->where('source', 'api')->sum('amount'),
        ];

        return view('admin.reseller.index', $data);
    }
}
