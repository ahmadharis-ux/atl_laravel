<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_admin || ! $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if (filled($user->pin_hash) || $request->routeIs('settings.pin', 'settings.pin.store', 'logout', 'verification.*')) {
            return $next($request);
        }

        return redirect()
            ->route('settings.pin')
            ->with('status', 'Atur PIN transaksi terlebih dahulu untuk mengamankan pembayaran saldo.');
    }
}
