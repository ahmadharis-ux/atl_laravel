<?php

use App\Http\Controllers\Admin\CustomerTopup\ProductController;
use App\Http\Controllers\Admin\CustomerTopup\TopupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\Product\ProductController as ProductModuleController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('/components', 'admin.components')->name('components');

    Route::get('/lookup/{type}', LookupController::class)->middleware('permission:products.view')->name('lookup');

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
    Route::post('/transactions/{order}/refund', [TransactionController::class, 'refund'])->middleware('permission:transactions.update')->name('transactions.refund');

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/prabayar', [ProductModuleController::class, 'prabayar'])->middleware('permission:products.view')->name('prepay.index');
        Route::get('/pascabayar', [ProductModuleController::class, 'pascabayar'])->middleware('permission:products.view')->name('postpaid.index');
        Route::get('/{product}', [ProductModuleController::class, 'show'])->middleware('permission:products.view')->name('show');
        Route::get('/{product}/edit', [ProductModuleController::class, 'edit'])->middleware('permission:products.update')->name('edit');
        Route::put('/{product}', [ProductModuleController::class, 'update'])->middleware('permission:products.update')->name('update');
    });
});
