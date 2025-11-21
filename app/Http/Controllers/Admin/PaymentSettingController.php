<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentSettingRequest;
use App\Repositories\Interfaces\PaymentSettingRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller for managing payment gateway settings in the admin panel.
 * 
 * Handles viewing and updating payment gateway configurations including
 * Stripe, Razorpay, and GPay settings. Payment settings control which
 * payment methods are available for subscription purchases. Requires
 * 'view payment settings' permission.
 */
class PaymentSettingController extends Controller
{
    public function __construct(
        private readonly PaymentSettingRepositoryInterface $paymentSettingRepository
    ) {
    }

    /**
     * Display the payment settings page.
     */
    public function index(): View
    {
        Gate::authorize('view payment settings');
        
        $settings = $this->paymentSettingRepository->getSettings();
        
        return view('admin.payment-settings.index', compact('settings'));
    }

    /**
     * Update the payment settings.
     */
    public function update(UpdatePaymentSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Only update keys if the gateway is enabled, otherwise set to null
        if (!($validated['enable_stripe'] ?? false)) {
            $validated['stripe_publishable_key'] = null;
            $validated['stripe_secret_key'] = null;
        }
        
        if (!($validated['enable_razorpay'] ?? false)) {
            $validated['razorpay_key_id'] = null;
            $validated['razorpay_key_secret'] = null;
        }
        
        if (!($validated['enable_gpay'] ?? false)) {
            $validated['gpay_upi_id'] = null;
        }

        $this->paymentSettingRepository->updateSettings($validated);

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'Payment settings updated successfully.');
    }
}

