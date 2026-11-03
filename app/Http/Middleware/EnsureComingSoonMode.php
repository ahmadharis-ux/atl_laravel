<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureComingSoonMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $launchDate = Carbon::parse(
            config('landing.coming_soon_until'),
            'Asia/Jakarta',
        );

        if (now()->lt($launchDate)) {
            $clientIp = $request->ip();
            $allowedIps = config('landing.coming_soon_allowed_ips', []);

            if ($clientIp !== null && in_array($clientIp, $allowedIps, true)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Atlantic Pedia segera hadir.',
                    'launch_at' => $launchDate->toIso8601String(),
                ], 503);
            }

            return response()->view('landing.coming-soon', [
                'launchDate' => $launchDate,
            ], 503);
        }

        return $next($request);
    }
}
