<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Merchandise;
use App\Support\MerchandiseRedeemer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchandiseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $items = Merchandise::query()
            ->active()
            ->orderBy('points_cost')
            ->get();

        $redemptions = $user->merchandiseRedemptions()
            ->with('merchandise')
            ->latest('id')
            ->limit(10)
            ->get();

        $pointHistory = $user->pointTransactions()
            ->latest('id')
            ->limit(15)
            ->get();

        return view('user.merchandise.index', [
            'items' => $items,
            'redemptions' => $redemptions,
            'pointHistory' => $pointHistory,
            'points' => (int) $user->points,
            'tier' => $user->tier(),
        ]);
    }

    public function redeem(Request $request, Merchandise $merchandise): RedirectResponse
    {
        MerchandiseRedeemer::redeem($request->user(), $merchandise);

        return back()->with('status', 'Penukaran berhasil! Admin akan memproses pengiriman merchandise kamu.');
    }
}
