<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\ResellerKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResellerController extends Controller
{
    /**
     * Panel reseller: status keanggotaan, API key, benefit, dan aksi.
     */
    public function show(Request $request): View
    {
        return view('user.settings.reseller', [
            'user' => $request->user(),
            'discountPercent' => (float) config('reseller.discount_percent', 0),
        ]);
    }

    /**
     * Dokumentasi API reseller (cara akses produk, API key, & transaksi).
     */
    public function docs(Request $request): View
    {
        return view('user.settings.reseller-docs', [
            'user' => $request->user(),
            'discountPercent' => (float) config('reseller.discount_percent', 0),
            'apiBase' => url('/api/v1/reseller'),
        ]);
    }

    /**
     * Aktifkan akun sebagai reseller + buat API key pertama.
     */
    public function activate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_reseller) {
            return back()->with('status', 'Akun kamu sudah aktif sebagai reseller.');
        }

        $key = ResellerKey::generate();

        $user->forceFill([
            'is_reseller' => true,
            'reseller_api_key' => $key['hash'],
            'reseller_api_key_hint' => $key['hint'],
            'reseller_since' => $user->reseller_since ?? now(),
        ])->save();

        return back()
            ->with('status', 'Selamat! Kamu kini reseller Atlantic Pedia. Salin API key kamu sekarang — key hanya ditampilkan satu kali.')
            ->with('api_key', $key['plain']);
    }

    /**
     * Buat ulang API key (key lama langsung tidak berlaku).
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller) {
            return back()->with('error', 'Aktifkan dulu keanggotaan reseller kamu.');
        }

        $key = ResellerKey::generate();

        $user->forceFill([
            'reseller_api_key' => $key['hash'],
            'reseller_api_key_hint' => $key['hint'],
        ])->save();

        return back()
            ->with('status', 'API key baru dibuat. Key lama sudah tidak berlaku — salin key baru sekarang.')
            ->with('api_key', $key['plain']);
    }

    /**
     * Nonaktifkan keanggotaan reseller & cabut API key.
     */
    public function deactivate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller) {
            return back()->with('error', 'Akun kamu bukan reseller aktif.');
        }

        $user->forceFill([
            'is_reseller' => false,
            'reseller_api_key' => null,
            'reseller_api_key_hint' => null,
        ])->save();

        return back()->with(
            'status',
            'Keanggotaan reseller dinonaktifkan & API key dicabut. Kamu bisa mengaktifkan kembali kapan saja.'
        );
    }
}
