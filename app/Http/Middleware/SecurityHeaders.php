<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (config('app.staging_noindex')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        if (config('security.csp_enforce')) {
            $response->headers->set(
                'Content-Security-Policy',
                (string) config('security.content_security_policy'),
            );
        } elseif (config('security.csp_report_only')) {
            $response->headers->set(
                'Content-Security-Policy-Report-Only',
                (string) config('security.content_security_policy'),
            );
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
