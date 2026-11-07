<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ResellerKey;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi API reseller memakai API key via header
 * `X-API-Key: ap_...` atau `Authorization: Bearer ap_...`.
 *
 * Key yang masuk di-hash dulu lalu dicocokkan dengan kolom
 * `users.reseller_api_key` (tersimpan sebagai hash SHA-256).
 */
class AuthenticateReseller
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = (string) ($request->bearerToken() ?: $request->header('X-API-Key'));

        if ($plain === '' && $request->routeIs('legacy.h2h.*')) {
            $plain = (string) $request->request->get('api_key', '');
        }

        if ($plain === '') {
            return response()->json([
                'message' => 'API key tidak ditemukan. Sertakan header X-API-Key atau Authorization: Bearer.',
            ], 401);
        }

        $user = User::query()
            ->where('is_reseller', true)
            ->where(function (Builder $query): void {
                $query->whereNull('reseller_status')
                    ->orWhere('reseller_status', 'active');
            })
            ->where('reseller_api_key', ResellerKey::hash($plain))
            ->first();

        if ($user === null) {
            return response()->json([
                'message' => 'API key tidak valid atau akun kamu bukan reseller aktif.',
            ], 401);
        }

        $allowedIps = $user->reseller_allowed_ips ?? [];

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json([
                'message' => 'Alamat IP server ini tidak diizinkan untuk API reseller.',
            ], 403);
        }

        $lastActivityThreshold = now()->subMinute();
        User::query()
            ->whereKey($user->getKey())
            ->where(function ($query) use ($lastActivityThreshold): void {
                $query->whereNull('reseller_last_api_at')
                    ->orWhere('reseller_last_api_at', '<=', $lastActivityThreshold);
            })
            ->update(['reseller_last_api_at' => now()]);

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
