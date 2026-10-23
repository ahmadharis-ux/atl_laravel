<?php

use App\Http\Controllers\Api\Reseller\AccountController;
use App\Http\Controllers\Api\Reseller\OrderController;
use App\Http\Controllers\Api\Reseller\ProductController;
use App\Http\Middleware\AuthenticateReseller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reseller API (v1)
|--------------------------------------------------------------------------
|
| Endpoint khusus reseller: daftar produk (harga reseller), pembuatan
| pesanan lewat saldo, riwayat pesanan, profil & saldo. Autentikasi
| memakai API key (header X-API-Key / Bearer) — tanpa sesi & CSRF.
|
*/

Route::prefix('v1/reseller')
    ->middleware(['throttle:reseller-api', AuthenticateReseller::class])
    ->group(function () {
        Route::get('/profile', [AccountController::class, 'profile']);
        Route::get('/balance', [AccountController::class, 'balance']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order:order_number}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
    });
