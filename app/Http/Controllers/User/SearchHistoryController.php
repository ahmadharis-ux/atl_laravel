<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    /**
     * Kosongkan seluruh riwayat pencarian milik pengguna yang login.
     */
    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        SearchHistory::clearFor((int) $request->user()->getKey());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('status', 'Riwayat pencarian dihapus.');
    }
}
