<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeatureSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.features.index', [
            'setting' => FeatureSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'reseller_enabled' => ['boolean'],
            'ticket_enabled' => ['boolean'],
            'merchandise_enabled' => ['boolean'],
            'dashboard_promo_enabled' => ['boolean'],
        ]);

        $setting = FeatureSetting::current();

        $setting->update([
            'reseller_enabled' => $request->boolean('reseller_enabled'),
            'ticket_enabled' => $request->boolean('ticket_enabled'),
            'merchandise_enabled' => $request->boolean('merchandise_enabled'),
            'dashboard_promo_enabled' => $request->boolean('dashboard_promo_enabled'),
        ]);

        return back()->with('status', 'Pengaturan halaman berhasil diperbarui.');
    }
}
