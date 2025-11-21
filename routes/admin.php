<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All routes for the admin portal/backend management system.
| These routes require authentication and admin role.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    
    // Dashboard - accessible by admin only
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('dashboard');

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        // Users Management
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        
        // Subscription Plans
        Route::resource('subscription-plans', \App\Http\Controllers\Admin\SubscriptionPlanController::class);
        
        // Subscriptions
        Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::resource('subscriptions', \App\Http\Controllers\Admin\SubscriptionController::class);
        
        // Payments
        Route::resource('payments', \App\Http\Controllers\Admin\PaymentController::class);
        
        // Invoices
        Route::resource('invoices', \App\Http\Controllers\Admin\InvoiceController::class);
        
        // Announcements & Notifications
        Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class)->except(['show']);
        Route::resource('notifications', \App\Http\Controllers\Admin\InAppNotificationController::class)->except(['show']);

        // Expenses
        Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class);
        
        // Incomes
        Route::resource('incomes', \App\Http\Controllers\Admin\IncomeController::class);

        // Finances Overview
        Route::get('/finances', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])
            ->name('finances.index');
        
        // Reports
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        
        // CMS Management (for frontend content)
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::resource('pages', \App\Http\Controllers\Admin\Cms\PageController::class);
            Route::resource('content', \App\Http\Controllers\Admin\Cms\ContentController::class);
        });
        
        // Landing Page Content Management
        Route::get('/landing-page', [\App\Http\Controllers\Admin\LandingPageController::class, 'index'])->name('landing-page.index');
        Route::put('/landing-page/{landingPage}', [\App\Http\Controllers\Admin\LandingPageController::class, 'update'])->name('landing-page.update');
        
        // Site Settings
        Route::get('/site-settings', [\App\Http\Controllers\Admin\SiteSettingsController::class, 'index'])
            ->middleware('permission:view site settings')
            ->name('site-settings.index');
        Route::put('/site-settings/{siteSetting}', [\App\Http\Controllers\Admin\SiteSettingsController::class, 'update'])
            ->middleware('permission:edit site settings')
            ->name('site-settings.update');
        
        // Banners Management
        Route::resource('banners', \App\Http\Controllers\Admin\BannerController::class);
        
        // Payment Settings
        Route::get('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingController::class, 'index'])
            ->name('payment-settings.index');
        Route::put('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingController::class, 'update'])
            ->name('payment-settings.update');
    });
    
    // Routes accessible by admin (permission-based)
    Route::middleware(['role:admin'])->group(function () {
        // Activity Logs (accessible by admin with permission)
        Route::get('/activities', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])
            ->middleware('permission:view activities')
            ->name('activities.index');

        // User Activity Overview (accessible by admin with permission)
        Route::get('/user-activity', [\App\Http\Controllers\Admin\UserActivityController::class, 'index'])
            ->middleware('permission:view activities')
            ->name('user-activity.index');
    });

    Route::middleware(['role:admin,member', 'permission:view announcements|view notifications'])->group(function () {
        Route::get('/notification-center', [\App\Http\Controllers\Admin\NotificationCenterController::class, 'index'])
            ->name('notification-center.index');
    });

    Route::middleware(['role:admin,member', 'permission:mark notifications read'])->group(function () {
        Route::post('/notification-center/{notification}/read', [\App\Http\Controllers\Admin\NotificationCenterController::class, 'markAsRead'])
            ->name('notification-center.read');
    });

    Route::middleware(['role:admin', 'permission:view reports|export reports'])->group(function () {
        Route::post('/exports/{type}', [\App\Http\Controllers\Admin\ExportController::class, 'export'])
            ->name('exports.export');
        Route::get('/exports/{export}/status', [\App\Http\Controllers\Admin\ExportController::class, 'status'])
            ->name('exports.status');
        Route::get('/exports/{export}/download', [\App\Http\Controllers\Admin\ExportController::class, 'download'])
            ->name('exports.download');
    });

});

