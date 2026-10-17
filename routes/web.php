<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FirebaseServiceWorkerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\BankAccountController as UserBankAccountController;
use App\Http\Controllers\User\CheckoutController as UserCheckoutController;
use App\Http\Controllers\User\ContactController as UserContactController;
use App\Http\Controllers\User\DepositController as UserDepositController;
use App\Http\Controllers\User\DeviceTokenController as UserDeviceTokenController;
use App\Http\Controllers\User\EwalletTokenizationController as UserEwalletTokenizationController;
use App\Http\Controllers\User\LookupController as UserLookupController;
use App\Http\Controllers\User\NicknameController as UserNicknameController;
use App\Http\Controllers\User\OrderController as UserOrderController;
use App\Http\Controllers\User\PhoneOperatorController as UserPhoneOperatorController;
use App\Http\Controllers\User\ProductController as UserProductController;
use App\Http\Controllers\User\PulsaController as UserPulsaController;
use App\Http\Controllers\User\SaldoController as UserSaldoController;
use App\Http\Controllers\User\SearchHistoryController as UserSearchHistoryController;
use App\Http\Controllers\User\SettingsController as UserSettingsController;
use App\Http\Controllers\Webhook\DigiflazzWebhookController;
use App\Http\Controllers\Webhook\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/digiflazz', DigiflazzWebhookController::class)->name('webhook.digiflazz');
Route::post('/webhook/midtrans', MidtransWebhookController::class)->name('webhook.midtrans');

Route::get('/firebase-messaging-sw.js', FirebaseServiceWorkerController::class)->name('firebase.sw');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

    Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])->name('two-factor.challenge.verify');

    Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'non-admin', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/saldo', [UserSaldoController::class, 'index'])->name('saldo.index');
    Route::get('/saldo/mutasi', [UserSaldoController::class, 'mutations'])->name('saldo.mutations');

    Route::get('/saldo/topup', [UserDepositController::class, 'create'])->name('deposits.create');
    Route::post('/saldo/topup', [UserDepositController::class, 'store'])->name('deposits.store');
    Route::get('/saldo/topup/riwayat', [UserDepositController::class, 'index'])->name('deposits.index');
    Route::get('/saldo/topup/{deposit}', [UserDepositController::class, 'show'])->name('deposits.show');
    Route::post('/saldo/topup/{deposit}/bayar', [UserDepositController::class, 'pay'])->name('deposits.pay');
    Route::post('/saldo/ewallet/link', [UserEwalletTokenizationController::class, 'link'])->name('ewallet.link');

    Route::get('/produk/lookup/{type}', UserLookupController::class)->name('products.lookup');
    Route::get('/produk/cek-nickname', UserNicknameController::class)->name('products.nickname');
    Route::get('/produk/deteksi-nomor', UserPhoneOperatorController::class)->name('products.detect-operator');
    Route::get('/produk', [UserProductController::class, 'index'])->name('products.index');
    Route::get('/produk/brand/{brand:slug}', [UserProductController::class, 'brand'])->name('products.brand');
    Route::get('/produk/{product}', [UserProductController::class, 'show'])->name('products.show');

    Route::get('/checkout/{product}', [UserCheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{product}', [UserCheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/pulsa', [UserPulsaController::class, 'index'])->name('pulsa.index');

    Route::get('/kontak', [UserContactController::class, 'index'])->name('contacts.index');
    Route::post('/kontak', [UserContactController::class, 'store'])->name('contacts.store');
    Route::get('/kontak/template', [UserContactController::class, 'template'])->name('contacts.template');
    Route::post('/kontak/import', [UserContactController::class, 'import'])->name('contacts.import');
    Route::put('/kontak/{contact}', [UserContactController::class, 'update'])->name('contacts.update');
    Route::delete('/kontak/{contact}', [UserContactController::class, 'destroy'])->name('contacts.destroy');

    Route::get('/transaksi', [UserOrderController::class, 'index'])->name('orders.index');
    Route::get('/transaksi/{order}', [UserOrderController::class, 'show'])->name('orders.show');
    Route::post('/transaksi/{order}/bayar', [UserOrderController::class, 'pay'])->name('orders.pay');
    Route::post('/transaksi/{order}/cek-status', [UserOrderController::class, 'sync'])->name('orders.sync');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/daftar', [NotificationController::class, 'list'])->name('notifications.list');
    Route::get('/notifikasi/{notification}/buka', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::post('/notifikasi/token', [UserDeviceTokenController::class, 'store'])->name('notifications.token.store');
    Route::delete('/notifikasi/token', [UserDeviceTokenController::class, 'destroy'])->name('notifications.token.destroy');

    Route::get('/pengaturan', [UserSettingsController::class, 'index'])->name('settings.index');

    Route::get('/pengaturan/profil', [UserSettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/pengaturan/profil', [UserSettingsController::class, 'updateProfile'])->name('settings.profile.update');

    Route::get('/pengaturan/kata-sandi', [UserSettingsController::class, 'password'])->name('settings.password');
    Route::put('/pengaturan/kata-sandi', [UserSettingsController::class, 'updatePassword'])->name('settings.password.update');

    Route::get('/pengaturan/notifikasi', [UserSettingsController::class, 'notifications'])->name('settings.notifications');

    Route::get('/pengaturan/rekening', [UserBankAccountController::class, 'index'])->name('settings.banks.index');
    Route::post('/pengaturan/rekening', [UserBankAccountController::class, 'store'])->name('settings.banks.store');
    Route::put('/pengaturan/rekening/{bankAccount}', [UserBankAccountController::class, 'update'])->name('settings.banks.update');
    Route::delete('/pengaturan/rekening/{bankAccount}', [UserBankAccountController::class, 'destroy'])->name('settings.banks.destroy');

    Route::get('/pengaturan/keamanan', [TwoFactorController::class, 'show'])->name('settings.two-factor');
    Route::post('/pengaturan/2fa/aktifkan', [TwoFactorController::class, 'enable'])->name('settings.two-factor.enable');
    Route::post('/pengaturan/2fa/konfirmasi', [TwoFactorController::class, 'confirm'])->name('settings.two-factor.confirm');
    Route::delete('/pengaturan/2fa', [TwoFactorController::class, 'disable'])->name('settings.two-factor.disable');

    Route::get('/pengaturan/perangkat', [UserSettingsController::class, 'sessions'])->name('settings.sessions');
    Route::post('/pengaturan/perangkat/keluar-lain', [UserSettingsController::class, 'logoutOtherDevices'])->name('settings.sessions.logout-others');

    Route::delete('/search-history', [UserSearchHistoryController::class, 'clear'])->name('search.history.clear');
});
