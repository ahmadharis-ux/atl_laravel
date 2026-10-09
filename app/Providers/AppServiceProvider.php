<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
