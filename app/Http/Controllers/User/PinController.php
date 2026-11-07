<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Rules\StrongPin;
use App\Support\PinResetService;
use App\Support\PinService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PinController extends Controller
{
    public function show(Request $request): View
    {
        return view('user.settings.pin', [
            'hasPin' => $request->user()->hasPin(),
        ]);
    }

    public function store(Request $request, PinService $pinService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasPin()) {
            return redirect()->route('settings.pin')->with('status', 'PIN transaksi sudah diatur.');
        }

        $validated = $request->validate([
            'pin' => ['required', 'digits:6', new StrongPin, 'confirmed'],
        ], [
            'pin.required' => 'PIN transaksi wajib diisi.',
            'pin.digits' => 'PIN harus terdiri dari 6 angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.',
        ]);

        $pinService->set($user, $validated['pin'], $request);

        return redirect()->route('settings.pin')->with('status', 'PIN transaksi berhasil dibuat.');
    }

    public function change(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasPin()) {
            return redirect()->route('settings.pin')
                ->with('status', 'Atur PIN transaksi terlebih dahulu.');
        }

        return view('user.settings.pin-change');
    }

    public function sendChangeOtp(Request $request, PinResetService $resetService): RedirectResponse
    {
        if (! $request->user()->hasPin()) {
            return redirect()->route('settings.pin')
                ->with('status', 'Atur PIN transaksi terlebih dahulu.');
        }

        $request->session()->forget('pin_reset_token');

        $resetService->sendOtp($request->user(), $request);

        return redirect()->route('settings.pin.change.verify')
            ->with('status', 'Kode OTP sudah dikirim ke email '.$request->user()->email.'.');
    }

    public function verify(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasPin()) {
            return redirect()->route('settings.pin')
                ->with('status', 'Atur PIN transaksi terlebih dahulu.');
        }

        return view('user.settings.pin-verify');
    }

    public function verifyStore(Request $request, PinResetService $resetService): RedirectResponse
    {
        if (! $request->user()->hasPin()) {
            return redirect()->route('settings.pin')
                ->with('status', 'Atur PIN transaksi terlebih dahulu.');
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        $token = $resetService->verifyOtp($request->user(), $validated['otp'], $request);

        $request->session()->put('pin_reset_token', $token);

        return redirect()->route('settings.pin.change.reset')
            ->with('status', 'Kode OTP terverifikasi. Buat PIN baru sekarang.');
    }

    public function reset(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasPin()) {
            return redirect()->route('settings.pin')
                ->with('status', 'Atur PIN transaksi terlebih dahulu.');
        }

        if (! $request->session()->has('pin_reset_token')) {
            return redirect()->route('settings.pin.change')
                ->with('error', 'Minta kode OTP baru untuk mengubah PIN transaksi.');
        }

        return view('user.settings.pin-reset');
    }

    public function resetStore(
        Request $request,
        PinService $pinService,
        PinResetService $resetService,
    ): RedirectResponse {
        if (! $request->user()->hasPin()) {
            return redirect()->route('settings.pin')
                ->with('status', 'Atur PIN transaksi terlebih dahulu.');
        }

        $validated = $request->validate([
            'pin' => ['required', 'digits:6', new StrongPin, 'confirmed'],
        ], [
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN harus terdiri dari 6 angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.',
        ]);

        $plainToken = $request->session()->get('pin_reset_token');
        $token = $resetService->findValidToken(
            $request->user(),
            is_string($plainToken) ? $plainToken : null,
        );

        if ($token === null) {
            $request->session()->forget('pin_reset_token');

            return redirect()->route('settings.pin.change')
                ->with('error', 'Sesi reset PIN tidak valid atau sudah kedaluwarsa. Minta kode OTP baru.');
        }

        $pinService->replace($request->user(), $validated['pin'], 'reset', $request, $token);

        $request->session()->forget('pin_reset_token');

        return redirect()->route('settings.pin')
            ->with('status', 'PIN transaksi berhasil diubah. Perangkat lain sudah dikeluarkan.');
    }
}
