<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StagingBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.staging_basic_auth')) {
            return $next($request);
        }

        $expectedUsername = (string) config('app.staging_basic_auth_username');
        $expectedPassword = (string) config('app.staging_basic_auth_password');

        if (
            hash_equals($expectedUsername, (string) $request->getUser())
            && hash_equals($expectedPassword, (string) $request->getPassword())
        ) {
            return $next($request);
        }

        return response('Authentication required.', 401, [
            'WWW-Authenticate' => 'Basic realm="Atlantic Pedia Staging"',
        ]);
    }
}
