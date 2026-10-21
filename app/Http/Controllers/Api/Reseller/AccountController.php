<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Profil reseller yang sedang autentikasi + benefit harga yang berlaku.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'is_reseller' => (bool) $user->is_reseller,
                'reseller_since' => $user->reseller_since?->toIso8601String(),
                'balance' => (int) $user->balance,
                'discount_percent' => (float) config('reseller.discount_percent', 0),
                'api_key_hint' => $user->reseller_api_key_hint,
            ],
        ]);
    }

    /**
     * Saldo akun + mutasi wallet terakhir (untuk rekonsiliasi reseller).
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = $user->walletTransactions()
            ->limit(10)
            ->get()
            ->map(fn ($transaction): array => [
                'type' => $transaction->type->value,
                'direction' => $transaction->direction->value,
                'amount' => (int) $transaction->amount,
                'balance_after' => (int) $transaction->balance_after,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at->toIso8601String(),
            ])
            ->all();

        return response()->json([
            'data' => [
                'balance' => (int) $user->balance,
                'transactions' => $transactions,
            ],
        ]);
    }
}
