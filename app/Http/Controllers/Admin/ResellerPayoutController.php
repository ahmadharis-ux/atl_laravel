<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResellerPayout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResellerPayoutController extends Controller
{
    public function index(Request $request): View
    {
        $payouts = ResellerPayout::query()
            ->with(['user:id,name,email', 'processor:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reseller.payouts', [
            'payouts' => $payouts,
            'stats' => [
                'pending' => ResellerPayout::query()->where('status', 'pending')->count(),
                'pendingAmount' => (int) ResellerPayout::query()->where('status', 'pending')->sum('amount'),
                'paidAmount' => (int) ResellerPayout::query()->where('status', 'paid')->sum('amount'),
            ],
        ]);
    }

    public function update(Request $request, ResellerPayout $payout): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:paid,rejected'],
            'admin_note' => ['required_if:action,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $payout, $validated): void {
            $locked = ResellerPayout::query()->whereKey($payout->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payout' => 'Permintaan ini sudah diproses.',
                ]);
            }

            $locked->forceFill([
                'status' => $validated['action'],
                'processed_by' => $request->user()->getKey(),
                'processed_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
            ])->save();
        });

        return back()->with(
            'success',
            $validated['action'] === 'paid'
                ? 'Pencairan ditandai sudah ditransfer.'
                : 'Permintaan pencairan ditolak; komisi dikembalikan ke saldo tersedia.',
        );
    }
}
