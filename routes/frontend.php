<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Website Routes
|--------------------------------------------------------------------------
|
| Routes for the public-facing single page website.
| These are accessible to everyone.
|
*/

// Public Frontend Routes
Route::name('frontend.')->group(function () {
    
    // Single Page Website (all sections on one page)
    Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])->name('home');
    
    // CMS Pages
    Route::get('/pages/{slug}', [\App\Http\Controllers\Frontend\PageController::class, 'show'])->name('pages.show');
    
    // Contact Form
    Route::post('/contact', [\App\Http\Controllers\Frontend\ContactController::class, 'store'])->name('contact.store');
    
    // Member Registration (public)
    Route::get('/register', [\App\Http\Controllers\Frontend\MemberController::class, 'register'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Frontend\MemberController::class, 'store'])->name('register.store');
});

// Member Portal (requires authentication)
Route::prefix('member')->name('member.')->middleware(['auth', 'role:member'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Frontend\MemberController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [\App\Http\Controllers\Frontend\MemberController::class, 'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Frontend\MemberController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [\App\Http\Controllers\Frontend\MemberController::class, 'updatePassword'])->name('password.update');
    Route::get('/subscriptions', [\App\Http\Controllers\Frontend\MemberController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/activities', [\App\Http\Controllers\Frontend\MemberController::class, 'activities'])->name('activities');
    Route::post('/check-in', [\App\Http\Controllers\Frontend\MemberController::class, 'checkIn'])->name('check-in');
    Route::post('/check-out', [\App\Http\Controllers\Frontend\MemberController::class, 'checkOut'])->name('check-out');
    Route::get('/download-video/{filename}', [\App\Http\Controllers\Frontend\MemberController::class, 'downloadVideo'])->name('download-video');
    // Subscription routes
            Route::prefix('subscription')->name('subscription.')->group(function () {
                Route::get('/checkout/{plan}', [\App\Http\Controllers\Member\CheckoutController::class, 'checkout'])->name('checkout');
                Route::post('/create/{plan}', [\App\Http\Controllers\Member\CheckoutController::class, 'create'])->name('create');
                Route::get('/success', [\App\Http\Controllers\Member\SubscriptionController::class, 'success'])->name('success');
                Route::get('/', [\App\Http\Controllers\Member\SubscriptionController::class, 'index'])->name('index');
                Route::post('/cancel/{subscription}', [\App\Http\Controllers\Member\SubscriptionController::class, 'cancel'])->name('cancel');
                Route::post('/refresh/{subscription}', [\App\Http\Controllers\Member\SubscriptionController::class, 'refresh'])->name('refresh');
            });
});

