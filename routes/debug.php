<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Debug route to check payment data (only in development)
if (app()->environment('local')) {
    Route::get('/debug/payment-data', function () {
        $paymentData = session('payment_data', []);
        
        return response()->json([
            'has_payment_data' => !empty($paymentData),
            'payment_data' => $paymentData,
            'has_client_secret' => isset($paymentData['client_secret']),
            'client_secret' => $paymentData['client_secret'] ?? null,
            'gateway' => $paymentData['gateway'] ?? null,
            'subscription_id' => $paymentData['subscription_id'] ?? null,
            'customer_id' => $paymentData['customer_id'] ?? null,
            'status' => $paymentData['status'] ?? null,
        ]);
    })->middleware('auth');
}

