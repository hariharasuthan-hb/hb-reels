<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGateway\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService
    ) {
        // Middleware is applied in routes/frontend.php
    }

    /**
     * Show checkout page for subscription plan.
     */
    public function checkout(SubscriptionPlan $plan): View|RedirectResponse
    {
        $user = auth()->user();


        // Allow upgrade by auto-canceling current subscription
        $activeSubscription = $user->subscriptions()
            ->active()
            ->first();

        if ($activeSubscription) {
            if ($activeSubscription->subscription_plan_id === $plan->id) {
                return redirect()->route('member.dashboard')
                    ->with('info', 'You already have this subscription plan.');
            }

            $this->cancelForUpgrade($activeSubscription);
        }

        // Check if plan is active
        if (!$plan->is_active) {
            return redirect()->route('member.dashboard')
                ->with('error', 'This subscription plan is not available.');
        }

        // Get available payment gateways
        $availableGateways = $this->paymentGatewayService->getAvailableGateways();

        if (empty($availableGateways)) {
            return redirect()->route('member.dashboard')
                ->with('error', 'No payment gateways are configured. Please contact support.');
        }

        // Get payment settings for Google Pay check
        $paymentSettings = \App\Models\PaymentSetting::getSettings();

        return view('frontend.member.subscription.checkout', [
            'plan' => $plan,
            'availableGateways' => $availableGateways,
            'hasTrial' => $plan->hasTrial(),
            'trialDays' => $plan->getTrialDays(),
            'enableGpay' => $paymentSettings->enable_gpay ?? false,
        ]);
    }

    /**
     * Create subscription and redirect to payment.
     */
    public function create(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $user = auth()->user();

        // Debug: Log incoming request
        Log::info('🔵 CheckoutController::create - Request received', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'request_method' => $request->method(),
            'request_data' => $request->all(),
            'has_gateway' => $request->has('gateway'),
            'gateway_value' => $request->input('gateway'),
        ]);

        try {
            $validated = $request->validate([
                'gateway' => ['required', 'in:stripe,razorpay'],
            ], [
                'gateway.required' => 'Please select a payment method.',
                'gateway.in' => 'Invalid payment gateway selected.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->route('member.subscription.checkout', $plan->id)
                ->withErrors($e->errors())
                ->withInput();
        }

        // Check if user already has an active subscription
        $activeSubscription = $user->subscriptions()
            ->active()
            ->first();

        if ($activeSubscription) {
            if ($activeSubscription->subscription_plan_id === $plan->id) {
                return redirect()->route('member.dashboard')
                    ->with('info', 'You already have this subscription plan.');
            }

            $this->cancelForUpgrade($activeSubscription);
        }

        try {
            $result = $this->paymentGatewayService->createSubscription(
                $user,
                $plan,
                $validated['gateway']
            );

            $result['gateway'] = $validated['gateway'];

            // Save subscription to database immediately
            $trialEndAt = null;
            if (isset($result['trial_end']) && $result['trial_end']) {
                try {
                    $trialEndAt = is_string($result['trial_end']) 
                        ? \Carbon\Carbon::parse($result['trial_end']) 
                        : $result['trial_end'];
                } catch (\Exception $e) {
                    // If parsing fails, leave as null
                    Log::warning('Failed to parse trial_end', ['trial_end' => $result['trial_end'] ?? null]);
                }
            }

            // Prepare metadata with payment intent IDs for webhook matching
            $metadata = $result;
            if (isset($result['client_secret'])) {
                // Extract payment intent or setup intent ID from client secret
                $clientSecret = $result['client_secret'];
                if (str_starts_with($clientSecret, 'pi_')) {
                    $paymentIntentId = explode('_secret_', $clientSecret)[0];
                    $metadata['payment_intent_id'] = $paymentIntentId;
                } elseif (str_starts_with($clientSecret, 'seti_')) {
                    $setupIntentId = explode('_secret_', $clientSecret)[0];
                    $metadata['setup_intent_id'] = $setupIntentId;
                }
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'gateway' => $validated['gateway'],
                'gateway_customer_id' => $result['customer_id'] ?? null,
                'gateway_subscription_id' => $result['subscription_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'trial_end_at' => $trialEndAt,
                'started_at' => now(),
                'metadata' => $metadata,
            ]);

            // Payment records will be created automatically via webhooks after payment confirmation
            // No need to create them here - they will be created when payment_intent.succeeded or invoice.payment_succeeded webhooks are received

            session([
                'subscription_data' => [
                    'plan_id' => $plan->id,
                    'gateway' => $validated['gateway'],
                    'result' => $result,
                    'subscription_id' => $subscription->id,
                ],
            ]);

            $request->session()->put('payment_data', $result);
            session()->save();
            
            return redirect()->route('member.subscription.checkout', $plan->id);

        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'gateway' => $validated['gateway'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Provide user-friendly error message
            $errorMessage = 'A processing error occurred. Please try again.';
            
            // If it's a Stripe API error, provide more specific message
            if (str_contains($e->getMessage(), 'Stripe') || str_contains($e->getMessage(), 'stripe')) {
                $errorMessage = 'Payment gateway error. Please check your payment details and try again.';
            }

            return redirect()->route('member.subscription.checkout', $plan->id)
                ->with('error', $errorMessage);
        }
    }

    /**
     * Cancel current subscription when upgrading to a new plan.
     */
    protected function cancelForUpgrade(?Subscription $subscription): void
    {
        if (!$subscription) {
            return;
        }

        try {
            $canceled = $this->paymentGatewayService->cancelSubscription($subscription);

            if ($canceled) {
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Auto cancel for upgrade failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
