<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\LookupController as UserLookupController;
use App\Http\Controllers\User\NicknameController as UserNicknameController;
use App\Http\Controllers\User\PhoneOperatorController as UserPhoneOperatorController;
use App\Http\Controllers\User\ProductController as UserProductController;
use App\Http\Controllers\User\SaldoController as UserSaldoController;
use App\Http\Controllers\User\SearchHistoryController as UserSearchHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/saldo', [UserSaldoController::class, 'index'])->name('saldo.index');

    Route::get('/produk/lookup/{type}', UserLookupController::class)->name('products.lookup');
    Route::get('/produk/cek-nickname', UserNicknameController::class)->name('products.nickname');
    Route::get('/produk/deteksi-nomor', UserPhoneOperatorController::class)->name('products.detect-operator');
    Route::get('/produk', [UserProductController::class, 'index'])->name('products.index');
    Route::get('/produk/{product}', [UserProductController::class, 'show'])->name('products.show');

    Route::delete('/search-history', [UserSearchHistoryController::class, 'clear'])->name('search.history.clear');
});
