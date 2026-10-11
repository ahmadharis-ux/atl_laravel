<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke area admin.');
        }

        return $next($request);
    }
}
