<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengarahkan admin ke panel admin ketika ia membuka halaman user/publik.
 *
 * Admin tidak memakai area transaksi sisi user, jadi halaman user selalu
 * dialihkan ke dashboard admin agar tidak rancu.
 */
class RedirectAdminToAdminPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
