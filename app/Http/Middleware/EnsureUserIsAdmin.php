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
            return redirect()->route('dashboard')
                ->withErrors(['admin' => 'Anda tidak memiliki akses ke area admin.']);
        }

        return $next($request);
    }
}
