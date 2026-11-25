<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserTier;
use App\Http\Controllers\Controller;
use App\Models\LoyaltySetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoyaltySettingController extends Controller
{
    public function index(): View
    {
        return view('admin.loyalty-setting.index', [
            'setting' => LoyaltySetting::current(),
            'tiers' => UserTier::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gold_points' => ['required', 'integer', 'min:1', 'max:1000000'],
            'platinum_points' => ['required', 'integer', 'min:1', 'max:1000000'],
            'silver_discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'gold_discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'platinum_discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ], [
            'gold_points.min' => 'Threshold Gold minimal 1 poin.',
            'platinum_points.min' => 'Threshold Platinum minimal 1 poin.',
        ]);

        if ($validated['platinum_points'] <= $validated['gold_points']) {
            return back()
                ->withInput()
                ->withErrors(['platinum_points' => 'Threshold Platinum harus lebih besar dari Gold.']);
        }

        $current = LoyaltySetting::current();
        $discount = fn (string $tier): float => (float) ($validated[$tier.'_discount_percent'] ?? $current->{$tier.'_discount_percent'});

        if ($discount('gold') < $discount('silver') || $discount('platinum') < $discount('gold')) {
            return back()
                ->withInput()
                ->withErrors(['platinum_discount_percent' => 'Diskon tier yang lebih tinggi tidak boleh lebih kecil dari tier di bawahnya.']);
        }

        $current->update($validated);
        LoyaltySetting::flushCache();

        return back()->with('status', 'Pengaturan tier dan harga berhasil diperbarui.');
    }
}
