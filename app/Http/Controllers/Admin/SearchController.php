<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Pencarian cepat lintas modul untuk kotak pencarian di topbar admin.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        $actor = $request->user();

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $groups = [];

        if ($actor->can('users.view')) {
            $items = User::query()
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (User $user): array => [
                    'icon' => 'person',
                    'text' => $user->name,
                    'hint' => $user->email,
                    'url' => route('admin.users.edit', $user),
                ])
                ->all();

            $groups[] = ['label' => 'Pengguna', 'items' => $items];
        }

        if ($actor->can('transactions.view')) {
            $items = Order::query()
                ->where(fn ($q) => $q->where('order_number', 'like', $like)->orWhere('target', 'like', $like))
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Order $order): array => [
                    'icon' => 'receipt_long',
                    'text' => $order->order_number,
                    'hint' => $order->target,
                    'url' => route('admin.transactions.show', $order),
                ])
                ->all();

            $groups[] = ['label' => 'Transaksi', 'items' => $items];
        }

        if ($actor->can('products.view')) {
            $items = Product::query()
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (Product $product): array => [
                    'icon' => 'inventory_2',
                    'text' => $product->name,
                    'hint' => $product->code,
                    'url' => route('admin.products.show', $product),
                ])
                ->all();

            $groups[] = ['label' => 'Produk', 'items' => $items];
        }

        return response()->json(['groups' => $groups]);
    }
}
