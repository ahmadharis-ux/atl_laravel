<?php

use App\Http\Controllers\Admin\Bot\DigiflazzController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CustomerTopup\ProductController;
use App\Http\Controllers\Admin\CustomerTopup\TopupController;
use App\Http\Controllers\Admin\DashboardBannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Landing\FaqController as LandingFaqController;
use App\Http\Controllers\Admin\Landing\ServiceController as LandingServiceController;
use App\Http\Controllers\Admin\Landing\SettingController as LandingSettingController;
use App\Http\Controllers\Admin\Landing\TestimonialController as LandingTestimonialController;
use App\Http\Controllers\Admin\LoginBannerController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\LoyaltySettingController;
use App\Http\Controllers\Admin\MasterBankController;
use App\Http\Controllers\Admin\MerchandiseController;
use App\Http\Controllers\Admin\MerchandiseRedemptionController;
use App\Http\Controllers\Admin\Product\ProductController as ProductModuleController;
use App\Http\Controllers\Admin\Product\SyncDigiflazzController;
use App\Http\Controllers\Admin\Queue\QueueMonitorController;
use App\Http\Controllers\Admin\Report\CustomerReportController;
use App\Http\Controllers\Admin\Report\ExportController;
use App\Http\Controllers\Admin\Report\OverviewController;
use App\Http\Controllers\Admin\Report\ProductReportController;
use App\Http\Controllers\Admin\Report\ProviderReportController;
use App\Http\Controllers\Admin\ResellerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\TicketController;
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
    Route::post('/transactions/{order}/settle', [TransactionController::class, 'settle'])->middleware('permission:transactions.update')->name('transactions.settle');
    Route::post('/transactions/{order}/expire', [TransactionController::class, 'expire'])->middleware('permission:transactions.update')->name('transactions.expire');
    Route::post('/transactions/{order}/refund', [TransactionController::class, 'refund'])->middleware('permission:transactions.update')->name('transactions.refund');

    Route::get('/resellers', [ResellerController::class, 'index'])->middleware('permission:resellers.view')->name('resellers.index');

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->middleware('permission:tickets.view')->name('index');
        Route::get('/{ticket}', [TicketController::class, 'show'])->middleware('permission:tickets.view')->name('show');
        Route::post('/{ticket}/balas', [TicketController::class, 'reply'])->middleware('permission:tickets.update')->name('reply');
        Route::put('/{ticket}/status', [TicketController::class, 'updateStatus'])->middleware('permission:tickets.update')->name('status');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::redirect('/', '/admin/reports/overview');
        Route::get('/overview', OverviewController::class)->name('overview');
        Route::get('/products', ProductReportController::class)->name('products');
        Route::get('/providers', ProviderReportController::class)->name('providers');
        Route::get('/customers', CustomerReportController::class)->name('customers');
        Route::get('/{section}/export', ExportController::class)
            ->whereIn('section', ['overview', 'products', 'providers', 'customers'])
            ->name('export');
    });

    Route::prefix('landing')->name('landing.')->group(function () {
        Route::get('/', [LandingSettingController::class, 'index'])->middleware('permission:landing.view')->name('settings');
        Route::put('/', [LandingSettingController::class, 'update'])->middleware('permission:landing.update')->name('settings.update');

        Route::get('/layanan', [LandingServiceController::class, 'index'])->middleware('permission:landing.view')->name('services');
        Route::post('/layanan', [LandingServiceController::class, 'store'])->middleware('permission:landing.update')->name('services.store');
        Route::put('/layanan/{service}', [LandingServiceController::class, 'update'])->middleware('permission:landing.update')->name('services.update');
        Route::delete('/layanan/{service}', [LandingServiceController::class, 'destroy'])->middleware('permission:landing.update')->name('services.destroy');

        Route::get('/testimoni', [LandingTestimonialController::class, 'index'])->middleware('permission:landing.view')->name('testimonials');
        Route::post('/testimoni', [LandingTestimonialController::class, 'store'])->middleware('permission:landing.update')->name('testimonials.store');
        Route::put('/testimoni/{testimonial}', [LandingTestimonialController::class, 'update'])->middleware('permission:landing.update')->name('testimonials.update');
        Route::delete('/testimoni/{testimonial}', [LandingTestimonialController::class, 'destroy'])->middleware('permission:landing.update')->name('testimonials.destroy');

        Route::get('/faq', [LandingFaqController::class, 'index'])->middleware('permission:landing.view')->name('faqs');
        Route::post('/faq', [LandingFaqController::class, 'store'])->middleware('permission:landing.update')->name('faqs.store');
        Route::put('/faq/{faq}', [LandingFaqController::class, 'update'])->middleware('permission:landing.update')->name('faqs.update');
        Route::delete('/faq/{faq}', [LandingFaqController::class, 'destroy'])->middleware('permission:landing.update')->name('faqs.destroy');
    });

    Route::get('/resellers', [ResellerController::class, 'index'])->middleware('permission:resellers.view')->name('resellers.index');

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->middleware('permission:tickets.view')->name('index');
        Route::get('/{ticket}', [TicketController::class, 'show'])->middleware('permission:tickets.view')->name('show');
        Route::post('/{ticket}/balas', [TicketController::class, 'reply'])->middleware('permission:tickets.update')->name('reply');
        Route::put('/{ticket}/status', [TicketController::class, 'updateStatus'])->middleware('permission:tickets.update')->name('status');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::redirect('/', '/admin/reports/overview');
        Route::get('/overview', OverviewController::class)->name('overview');
        Route::get('/products', ProductReportController::class)->name('products');
        Route::get('/providers', ProviderReportController::class)->name('providers');
        Route::get('/customers', CustomerReportController::class)->name('customers');
        Route::get('/{section}/export', ExportController::class)
            ->whereIn('section', ['overview', 'products', 'providers', 'customers'])
            ->name('export');
    });

    Route::prefix('landing')->name('landing.')->group(function () {
        Route::get('/', [LandingSettingController::class, 'index'])->middleware('permission:landing.view')->name('settings');
        Route::put('/', [LandingSettingController::class, 'update'])->middleware('permission:landing.update')->name('settings.update');

        Route::get('/layanan', [LandingServiceController::class, 'index'])->middleware('permission:landing.view')->name('services');
        Route::post('/layanan', [LandingServiceController::class, 'store'])->middleware('permission:landing.update')->name('services.store');
        Route::put('/layanan/{service}', [LandingServiceController::class, 'update'])->middleware('permission:landing.update')->name('services.update');
        Route::delete('/layanan/{service}', [LandingServiceController::class, 'destroy'])->middleware('permission:landing.update')->name('services.destroy');

        Route::get('/testimoni', [LandingTestimonialController::class, 'index'])->middleware('permission:landing.view')->name('testimonials');
        Route::post('/testimoni', [LandingTestimonialController::class, 'store'])->middleware('permission:landing.update')->name('testimonials.store');
        Route::put('/testimoni/{testimonial}', [LandingTestimonialController::class, 'update'])->middleware('permission:landing.update')->name('testimonials.update');
        Route::delete('/testimoni/{testimonial}', [LandingTestimonialController::class, 'destroy'])->middleware('permission:landing.update')->name('testimonials.destroy');

        Route::get('/faq', [LandingFaqController::class, 'index'])->middleware('permission:landing.view')->name('faqs');
        Route::post('/faq', [LandingFaqController::class, 'store'])->middleware('permission:landing.update')->name('faqs.store');
        Route::put('/faq/{faq}', [LandingFaqController::class, 'update'])->middleware('permission:landing.update')->name('faqs.update');
        Route::delete('/faq/{faq}', [LandingFaqController::class, 'destroy'])->middleware('permission:landing.update')->name('faqs.destroy');
    });

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

    Route::prefix('merchandise')->name('merchandise.')->group(function () {
        Route::get('/', [MerchandiseController::class, 'index'])->middleware('permission:merchandise.view')->name('index');
        Route::post('/', [MerchandiseController::class, 'store'])->middleware('permission:merchandise.update')->name('store');
        Route::put('/{merchandise}', [MerchandiseController::class, 'update'])->middleware('permission:merchandise.update')->name('update');
        Route::delete('/{merchandise}', [MerchandiseController::class, 'destroy'])->middleware('permission:merchandise.update')->name('destroy');
    });

    Route::prefix('redemptions')->name('redemptions.')->group(function () {
        Route::get('/', [MerchandiseRedemptionController::class, 'index'])->middleware('permission:redemptions.view')->name('index');
        Route::post('/{redemption}/complete', [MerchandiseRedemptionController::class, 'complete'])->middleware('permission:redemptions.update')->name('complete');
        Route::post('/{redemption}/cancel', [MerchandiseRedemptionController::class, 'cancel'])->middleware('permission:redemptions.update')->name('cancel');
    });

    Route::prefix('loyalty-settings')->name('loyalty-settings.')->group(function () {
        Route::get('/', [LoyaltySettingController::class, 'index'])->middleware('permission:loyalty.view')->name('index');
        Route::put('/', [LoyaltySettingController::class, 'update'])->middleware('permission:loyalty.update')->name('update');
    });

    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', [FeatureSettingController::class, 'index'])->middleware('permission:features.view')->name('index');
        Route::put('/', [FeatureSettingController::class, 'update'])->middleware('permission:features.update')->name('update');
    });

    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\Queue\QueueMonitorController::class, 'index'])->middleware('permission:products.view')->name('index');
        Route::get('/failed/{uuid}', [App\Http\Controllers\Admin\Queue\QueueMonitorController::class, 'showFailed'])->middleware('permission:products.view')->name('failed.show');
        Route::post('/retry/{uuid}', [App\Http\Controllers\Admin\Queue\QueueMonitorController::class, 'retry'])->middleware('permission:products.update')->name('retry');
        Route::post('/forget/{uuid}', [App\Http\Controllers\Admin\Queue\QueueMonitorController::class, 'forget'])->middleware('permission:products.update')->name('forget');
        Route::post('/flush', [App\Http\Controllers\Admin\Queue\QueueMonitorController::class, 'flush'])->middleware('permission:products.update')->name('flush');
        Route::post('/retry-all', [App\Http\Controllers\Admin\Queue\QueueMonitorController::class, 'retryAll'])->middleware('permission:products.update')->name('retry-all');
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
        Route::post('/bulk-markup', [ProductModuleController::class, 'bulkMarkup'])->middleware('permission:products.update')->name('bulk-markup');

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
