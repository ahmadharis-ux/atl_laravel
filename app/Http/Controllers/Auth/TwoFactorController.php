<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('user.settings.two-factor', [
            'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
            'twoFactorPending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'twoFactorSecret' => $user->two_factor_secret,
            'twoFactorUri' => $user->two_factor_secret
                ? TwoFactorAuthentication::otpauthUri($user->two_factor_secret, config('app.name').':'.$user->email, config('app.name'))
                : null,
            'recoveryCodesCount' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        if ($user->two_factor_confirmed_at !== null) {
            return back()->withErrors(['two_factor' => '2FA sudah aktif pada akun ini.']);
        }

        $user->forceFill([
            'two_factor_secret' => TwoFactorAuthentication::generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'Masukkan secret ke aplikasi authenticator, lalu konfirmasi dengan kode 6 digit.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if ($user->two_factor_secret === null) {
            return back()->withErrors(['two_factor' => 'Aktifkan 2FA terlebih dahulu.']);
        }

        if (! TwoFactorAuthentication::verify($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => 'Kode verifikasi tidak valid.']);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => TwoFactorAuthentication::generateRecoveryCodes(),
        ])->save();

        return back()->with('status', '2FA aktif. Simpan kode pemulihan Anda di tempat yang aman.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', '2FA berhasil dinonaktifkan.');
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('auth.two_factor.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $userId = $request->session()->get('auth.two_factor.id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $this->validCode($user, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'Kode verifikasi tidak valid.']);
        }

        $remember = (bool) $request->session()->pull('auth.two_factor.remember', false);
        $request->session()->forget('auth.two_factor.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->can('dashboard.view') ? route('admin.dashboard') : route('dashboard'),
        );
    }

    private function validCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret && TwoFactorAuthentication::verify($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $code = strtoupper(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_filter($codes, fn (string $item): bool => $item !== $code)),
        ])->save();

        return true;
    }
}
