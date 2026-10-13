<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialiteController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        if ((string) config('services.google.client_id') === '') {
            return redirect()->route('login')->withErrors(['email' => 'Login Google belum dikonfigurasi.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal masuk dengan Google. Silakan coba lagi.']);
        }

        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if ($user) {
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Pengguna Google'),
                'email' => $googleUser->getEmail(),
                'password' => Str::random(32),
                'is_admin' => false,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(Roles::USER);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(
            $user->can('dashboard.view') ? route('admin.dashboard') : route('dashboard'),
        );
    }
}
