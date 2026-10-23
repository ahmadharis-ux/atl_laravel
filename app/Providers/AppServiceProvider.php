<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        /*
        |--------------------------------------------------------------
        | Rate limit API reseller (per API key, fallback IP).
        |--------------------------------------------------------------
        */
        RateLimiter::for('reseller-api', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->header('X-API-Key') ?: $request->ip());
        });

        View::composer(['partials.sidebar', 'partials.topbar'], function ($view): void {
            $authUser = auth()->user();
            $now = now()->locale('id');

            $view->with([
                'user' => [
                    'name' => $authUser?->name ?? 'Tamu',
                    'role' => $authUser?->is_admin ? 'Administrator' : 'Merchant',
                ],
                'dateLong' => $now->translatedFormat('l, d F Y'),
                'dateShort' => $now->translatedFormat('l, d F'),
            ]);
        });
    }
}
