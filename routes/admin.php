<?php

use App\Http\Controllers\Admin\Bot\DigiflazzController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CustomerTopup\ProductController;
use App\Http\Controllers\Admin\CustomerTopup\TopupController;
use App\Http\Controllers\Admin\DashboardBannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginBannerController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\MasterBankController;
use App\Http\Controllers\Admin\Product\ProductController as ProductModuleController;
use App\Http\Controllers\Admin\Product\SyncDigiflazzController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('/components', 'admin.components')->name('components');

    Route::get('/lookup/{type}', LookupController::class)->middleware('permission:products.view')->name('lookup');

    Route::get('/search', SearchController::class)->name('search');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    Route::get('/customer-topup/topup', [TopupController::class, 'index'])->middleware('permission:topups.view')->name('topup.index');
    Route::get('/customer-topup/topup/{deposit}', [TopupController::class, 'show'])->middleware('permission:topups.view')->name('topup.show');
    Route::post('/customer-topup/topup/{deposit}/settle', [TopupController::class, 'settle'])->middleware('permission:topups.update')->name('topup.settle');
    Route::post('/customer-topup/topup/{deposit}/expire', [TopupController::class, 'expire'])->middleware('permission:topups.update')->name('topup.expire');
    Route::get('/customer-topup/product', [ProductController::class, 'index'])->middleware('permission:products.view')->name('product.index');

    Route::get('/transactions', [TransactionController::class, 'index'])->middleware('permission:transactions.view')->name('transactions.index');
    Route::get('/transactions/{order}', [TransactionController::class, 'show'])->middleware('permission:transactions.view')->name('transactions.show');
    Route::get('/transactions/{order}/invoice', [TransactionController::class, 'invoice'])->middleware('permission:transactions.view')->name('transactions.invoice');
    Route::post('/transactions/{order}/refund', [TransactionController::class, 'refund'])->middleware('permission:transactions.update')->name('transactions.refund');

    Route::prefix('master-bank')->name('master-banks.')->group(function () {
        Route::get('/', [MasterBankController::class, 'index'])->middleware('permission:master-banks.view')->name('index');
        Route::post('/', [MasterBankController::class, 'store'])->middleware('permission:master-banks.update')->name('store');
        Route::put('/{masterBank}', [MasterBankController::class, 'update'])->middleware('permission:master-banks.update')->name('update');
        Route::delete('/{masterBank}', [MasterBankController::class, 'destroy'])->middleware('permission:master-banks.update')->name('destroy');
    });

    Route::prefix('master-brand')->name('master-brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->middleware('permission:master-brands.view')->name('index');
        Route::put('/{brand}', [BrandController::class, 'update'])->middleware('permission:master-brands.update')->name('update');
    });

    Route::prefix('banner-login')->name('login-banners.')->group(function () {
        Route::get('/', [LoginBannerController::class, 'index'])->middleware('permission:login-banners.view')->name('index');
        Route::post('/', [LoginBannerController::class, 'store'])->middleware('permission:login-banners.update')->name('store');
        Route::put('/{loginBanner}', [LoginBannerController::class, 'update'])->middleware('permission:login-banners.update')->name('update');
        Route::delete('/{loginBanner}', [LoginBannerController::class, 'destroy'])->middleware('permission:login-banners.update')->name('destroy');
    });

    Route::prefix('banner-dashboard')->name('dashboard-banners.')->group(function () {
        Route::get('/', [DashboardBannerController::class, 'index'])->middleware('permission:dashboard-banners.view')->name('index');
        Route::post('/', [DashboardBannerController::class, 'store'])->middleware('permission:dashboard-banners.update')->name('store');
        Route::put('/{dashboardBanner}', [DashboardBannerController::class, 'update'])->middleware('permission:dashboard-banners.update')->name('update');
        Route::delete('/{dashboardBanner}', [DashboardBannerController::class, 'destroy'])->middleware('permission:dashboard-banners.update')->name('destroy');
    });

    Route::prefix('bot/digiflazz')->name('bot.digiflazz.')->group(function () {
        Route::get('/', [DigiflazzController::class, 'index'])->middleware('permission:products.view')->name('index');
        Route::get('/create', [DigiflazzController::class, 'create'])->middleware('permission:products.update')->name('create');
        Route::post('/', [DigiflazzController::class, 'store'])->middleware('permission:products.update')->name('store');
        Route::get('/{digiflazz}/edit', [DigiflazzController::class, 'edit'])->middleware('permission:products.update')->name('edit');
        Route::put('/{digiflazz}', [DigiflazzController::class, 'update'])->middleware('permission:products.update')->name('update');
        Route::delete('/{digiflazz}', [DigiflazzController::class, 'destroy'])->middleware('permission:products.update')->name('destroy');

        Route::get('/sync', [DigiflazzController::class, 'syncIndex'])->middleware('permission:products.view')->name('sync.index');
        Route::get('/sync/{syncLog}', [DigiflazzController::class, 'syncShow'])->middleware('permission:products.view')->name('sync.show');
        Route::post('/sync/start', [DigiflazzController::class, 'startSync'])->middleware('permission:products.update')->name('sync.start');
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/prabayar', [ProductModuleController::class, 'prabayar'])->middleware('permission:products.view')->name('prepay.index');
        Route::get('/pascabayar', [ProductModuleController::class, 'pascabayar'])->middleware('permission:products.view')->name('postpaid.index');

        Route::prefix('sync-digiflazz')->name('sync-digiflazz.')->group(function () {
            Route::get('/', [SyncDigiflazzController::class, 'index'])->middleware('permission:products.view')->name('index');
            Route::post('/start', [SyncDigiflazzController::class, 'start'])->middleware('permission:products.update')->name('start');
            Route::get('/{syncLog}', [SyncDigiflazzController::class, 'show'])->middleware('permission:products.view')->name('show');
        });

        Route::get('/{product}', [ProductModuleController::class, 'show'])->middleware('permission:products.view')->name('show');
        Route::get('/{product}/edit', [ProductModuleController::class, 'edit'])->middleware('permission:products.update')->name('edit');
        Route::put('/{product}', [ProductModuleController::class, 'update'])->middleware('permission:products.update')->name('update');
    });
});
