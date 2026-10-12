<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\CheckoutController as UserCheckoutController;
use App\Http\Controllers\User\DepositController as UserDepositController;
use App\Http\Controllers\User\LookupController as UserLookupController;
use App\Http\Controllers\User\NicknameController as UserNicknameController;
use App\Http\Controllers\User\OrderController as UserOrderController;
use App\Http\Controllers\User\PhoneOperatorController as UserPhoneOperatorController;
use App\Http\Controllers\User\ProductController as UserProductController;
use App\Http\Controllers\User\PulsaController as UserPulsaController;
use App\Http\Controllers\User\SaldoController as UserSaldoController;
use App\Http\Controllers\User\SearchHistoryController as UserSearchHistoryController;
use App\Http\Controllers\Webhook\DigiflazzWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/digiflazz', DigiflazzWebhookController::class)->name('webhook.digiflazz');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'non-admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/saldo', [UserSaldoController::class, 'index'])->name('saldo.index');
    Route::get('/saldo/mutasi', [UserSaldoController::class, 'mutations'])->name('saldo.mutations');

    Route::get('/saldo/topup', [UserDepositController::class, 'create'])->name('deposits.create');
    Route::post('/saldo/topup', [UserDepositController::class, 'store'])->name('deposits.store');
    Route::get('/saldo/topup/{deposit}', [UserDepositController::class, 'show'])->name('deposits.show');
    Route::post('/saldo/topup/{deposit}/bayar', [UserDepositController::class, 'pay'])->name('deposits.pay');

    Route::get('/produk/lookup/{type}', UserLookupController::class)->name('products.lookup');
    Route::get('/produk/cek-nickname', UserNicknameController::class)->name('products.nickname');
    Route::get('/produk/deteksi-nomor', UserPhoneOperatorController::class)->name('products.detect-operator');
    Route::get('/produk', [UserProductController::class, 'index'])->name('products.index');
    Route::get('/produk/{product}', [UserProductController::class, 'show'])->name('products.show');

    Route::get('/checkout/{product}', [UserCheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{product}', [UserCheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/pulsa', [UserPulsaController::class, 'index'])->name('pulsa.index');

    Route::get('/transaksi', [UserOrderController::class, 'index'])->name('orders.index');
    Route::get('/transaksi/{order}', [UserOrderController::class, 'show'])->name('orders.show');
    Route::post('/transaksi/{order}/bayar', [UserOrderController::class, 'pay'])->name('orders.pay');

    Route::delete('/search-history', [UserSearchHistoryController::class, 'clear'])->name('search.history.clear');
});
