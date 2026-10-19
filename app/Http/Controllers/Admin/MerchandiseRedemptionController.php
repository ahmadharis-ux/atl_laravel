<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MerchandiseRedemptionStatus;
use App\Http\Controllers\Controller;
use App\Models\MerchandiseRedemption;
use App\Support\MerchandiseRedeemer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MerchandiseRedemptionController extends Controller
{
    public function index(Request $request): View
    {
        $redemptions = MerchandiseRedemption::query()
            ->with(['user', 'merchandise'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('merchandise', fn ($m) => $m->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.redemption.index', [
            'redemptions' => $redemptions,
            'listRoute' => route('admin.redemptions.index'),
        ]);
    }

    public function complete(Request $request, MerchandiseRedemption $redemption): RedirectResponse
    {
        if (! MerchandiseRedeemer::complete($redemption, $request->input('notes'))) {
            return back()->with('error', 'Hanya penukaran berstatus Menunggu Diproses yang dapat diselesaikan.');
        }

        return back()->with('status', 'Penukaran ditandai selesai.');
    }

    public function cancel(Request $request, MerchandiseRedemption $redemption): RedirectResponse
    {
        if (! MerchandiseRedeemer::cancel($redemption, $request->input('notes'))) {
            return back()->with('error', 'Hanya penukaran berstatus Menunggu Diproses yang dapat dibatalkan.');
        }

        return back()->with('status', 'Penukaran dibatalkan dan poin dikembalikan.');
    }

    /**
     * @return array<string, string>
     */
    public static function statusFilters(): array
    {
        return collect(MerchandiseRedemptionStatus::cases())
            ->mapWithKeys(fn (MerchandiseRedemptionStatus $status): array => [$status->value => $status->label()])
            ->all();
    }
}
