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
            'reseller_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ticket_enabled' => ['boolean'],
            'merchandise_enabled' => ['boolean'],
            'dashboard_promo_enabled' => ['boolean'],
            'deposit_qris_enabled' => ['sometimes', 'boolean'],
            'deposit_transfer_enabled' => ['sometimes', 'boolean'],
            'deposit_ewallet_enabled' => ['sometimes', 'boolean'],
            'transaction_saldo_enabled' => ['sometimes', 'boolean'],
            'transaction_qris_enabled' => ['sometimes', 'boolean'],
        ]);

        $setting = FeatureSetting::current();

        $settings = [
            'reseller_enabled' => $request->boolean('reseller_enabled'),
            'reseller_discount_percent' => $request->filled('reseller_discount_percent')
                ? $request->input('reseller_discount_percent')
                : null,
            'ticket_enabled' => $request->boolean('ticket_enabled'),
            'merchandise_enabled' => $request->boolean('merchandise_enabled'),
            'dashboard_promo_enabled' => $request->boolean('dashboard_promo_enabled'),
        ];

        foreach ([
            'deposit_qris_enabled',
            'deposit_transfer_enabled',
            'deposit_ewallet_enabled',
            'transaction_saldo_enabled',
            'transaction_qris_enabled',
        ] as $column) {
            if ($request->exists($column)) {
                $settings[$column] = $request->boolean($column);
            }
        }

        $setting->update($settings);

        return back()->with('status', 'Pengaturan halaman dan metode pembayaran berhasil diperbarui.');
    }
}
