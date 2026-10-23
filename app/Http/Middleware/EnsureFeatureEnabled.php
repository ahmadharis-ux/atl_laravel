<?php

namespace App\Http\Middleware;

use App\Models\FeatureSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menampilkan halaman "Coming Soon" bila sebuah fitur halaman pengguna
 * dinonaktifkan lewat admin (reseller, ticket, merchandise).
 */
class EnsureFeatureEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (FeatureSetting::enabled($feature)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Fitur ini sedang dalam pengembangan.',
            ], 503);
        }

        return response()->view('errors.coming-soon', [
            'feature' => $feature,
        ], 200);
    }
}
