<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PaymentGateway\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService
    ) {
        // Middleware is applied in routes/frontend.php
    }

    /**
     * Show subscription overview page.
     */
    public function index(): View
    {
        $user = auth()->user();
        
        $subscriptions = $user->subscriptions()
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeSubscription = $subscriptions->firstWhere('status', 'active') 
            ?? $subscriptions->firstWhere('status', 'trialing');

        return view('frontend.member.subscription.index', [
            'subscriptions' => $subscriptions,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Subscription $subscription): RedirectResponse
    {
        $user = auth()->user();

        // Verify subscription belongs to user
        if ($subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Check if subscription can be canceled
        if ($subscription->isCanceled()) {
            return redirect()->route('member.subscription.index')
                ->with('info', 'This subscription is already canceled.');
        }

        try {
            $canceled = $this->paymentGatewayService->cancelSubscription($subscription);

            if ($canceled) {
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);

                return redirect()->route('member.subscription.index')
                    ->with('success', 'Subscription canceled successfully. It will remain active until the end of the current billing period.');
            } else {
                return redirect()->route('member.subscription.index')
                    ->with('error', 'Failed to cancel subscription. Please try again or contact support.');
            }
        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('member.subscription.index')
                ->with('error', 'An error occurred while canceling the subscription. Please contact support.');
        }
    }

    /**
     * Handle subscription success (after payment).
     */
    public function success(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        $sessionId = $request->query('session_id');
        $paymentIntentId = $request->query('payment_intent');
        $setupIntentId = $request->query('setup_intent');
        $razorpayPaymentId = $request->query('razorpay_payment_id');
        $subscriptionData = session('subscription_data');

        // Find the most recent pending subscription for this user
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($subscription) {
            // Verify and update subscription status based on gateway
            if ($subscription->gateway === 'stripe') {
                $this->verifyStripeSubscription($subscription, $paymentIntentId, $setupIntentId);
            } elseif ($subscription->gateway === 'razorpay' && $razorpayPaymentId) {
                $this->verifyRazorpaySubscription($subscription, $razorpayPaymentId);
            }
        }

        // Refresh subscription to get updated status
        if ($subscription) {
            $subscription->refresh();
            
            // After verification, check if payment record exists and create if missing
            if ($subscription->gateway === 'stripe' && ($subscription->status === 'active' || $subscription->status === 'trialing')) {
                $hasPayment = Payment::where('subscription_id', $subscription->id)->exists();
                if (!$hasPayment) {
                    // Try to create payment from subscription's latest invoice or payment intent
                    $this->createPaymentFromSubscriptionVerification($subscription, $paymentIntentId);
                }
            }
        }

        return view('frontend.member.subscription.success', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Verify and update Stripe subscription status.
     */
    protected function verifyStripeSubscription(Subscription $subscription, ?string $paymentIntentId = null, ?string $setupIntentId = null): void
    {
        try {
            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $stripe = new \Stripe\StripeClient($paymentSettings->stripe_secret_key);

            // If we have a subscription ID, check its status
            if ($subscription->gateway_subscription_id) {
                try {
                    $stripeSubscription = $stripe->subscriptions->retrieve($subscription->gateway_subscription_id);
                    
                    $status = $this->mapStripeStatus($stripeSubscription->status);
                    
                    // Also check the latest invoice to see if payment succeeded
                    $invoice = null;
                    if (isset($stripeSubscription->latest_invoice)) {
                        $invoice = is_string($stripeSubscription->latest_invoice)
                            ? $stripe->invoices->retrieve($stripeSubscription->latest_invoice)
                            : $stripeSubscription->latest_invoice;
                        
                        // If invoice is paid and subscription is still pending, activate it
                        if ($invoice->paid && $subscription->status === 'pending') {
                            $plan = $subscription->subscriptionPlan;
                            $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                        }
                    }
                    
                    $oldStatus = $subscription->status;
                    $subscription->update([
                        'status' => $status,
                        'trial_end_at' => $stripeSubscription->trial_end ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) : null,
                        'next_billing_at' => $stripeSubscription->current_period_end ? date('Y-m-d H:i:s', $stripeSubscription->current_period_end) : null,
                        'started_at' => $stripeSubscription->start_date ? date('Y-m-d H:i:s', $stripeSubscription->start_date) : now(),
                    ]);

                    // Create payment record if subscription is active/trialing and invoice is paid
                    // Check if payment already exists for this subscription
                    $hasPayment = Payment::where('subscription_id', $subscription->id)->exists();
                    
                    // Create payment if:
                    // 1. Subscription is active/trialing AND invoice is paid AND no payment exists yet
                    // 2. OR status changed from pending to active/trialing AND invoice is paid
                    if (($status === 'active' || $status === 'trialing') && isset($invoice) && $invoice->paid && !$hasPayment) {
                        $this->createPaymentFromStripeInvoice($subscription, $invoice);
                    } elseif ($oldStatus === 'pending' && ($status === 'active' || $status === 'trialing') && isset($invoice) && $invoice->paid) {
                        // Also create if status changed from pending to active/trialing
                        $this->createPaymentFromStripeInvoice($subscription, $invoice);
                    } elseif (($status === 'active' || $status === 'trialing') && !$hasPayment && !isset($invoice)) {
                        // If subscription is active but no invoice available, create payment from subscription data
                        $this->createPaymentFromSubscription($subscription);
                    }

                        'subscription_id' => $subscription->id,
                        'stripe_subscription_id' => $subscription->gateway_subscription_id,
                        'status' => $status,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve Stripe subscription', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Also check payment/setup intent if provided
            if ($paymentIntentId || $setupIntentId) {
                // Verify payment/setup intent status
                $intentId = $paymentIntentId ?? $setupIntentId;
                $intentType = $paymentIntentId ? 'payment_intent' : 'setup_intent';
                
                if ($intentType === 'payment_intent') {
                    $intent = $stripe->paymentIntents->retrieve($intentId);
                } else {
                    $intent = $stripe->setupIntents->retrieve($intentId);
                }

                if ($intent->status === 'succeeded') {
                    // Payment succeeded, update subscription to active or trialing
                    $plan = $subscription->subscriptionPlan;
                    $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                    $oldStatus = $subscription->status;
                    
                    $subscription->update([
                        'status' => $status,
                        'started_at' => now(),
                    ]);

                    // Create payment record if subscription is active/trialing
                    // Check if payment already exists for this subscription
                    $hasPayment = Payment::where('subscription_id', $subscription->id)->exists();
                    
                    if (($status === 'active' || $status === 'trialing') && !$hasPayment) {
                        $this->createPaymentFromStripeIntent($subscription, $intent, $intentType);
                    } elseif ($oldStatus === 'pending' && ($status === 'active' || $status === 'trialing')) {
                        // Also create if status changed from pending to active/trialing
                        $this->createPaymentFromStripeIntent($subscription, $intent, $intentType);
                    }

                        'subscription_id' => $subscription->id,
                        'intent_id' => $intentId,
                        'status' => $status,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify and update Razorpay subscription status.
     */
    protected function verifyRazorpaySubscription(Subscription $subscription, string $paymentId): void
    {
        try {
            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $razorpay = new \Razorpay\Api\Api($paymentSettings->razorpay_key_id, $paymentSettings->razorpay_key_secret);
            
            $payment = $razorpay->payment->fetch($paymentId);
            
            if ($payment->status === 'captured' || $payment->status === 'authorized') {
                $plan = $subscription->subscriptionPlan;
                $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                $oldStatus = $subscription->status;
                
                $subscription->update([
                    'status' => $status,
                    'started_at' => now(),
                ]);

                // Create payment record if subscription is active/trialing
                // Check if payment already exists for this subscription
                $hasPayment = Payment::where('subscription_id', $subscription->id)->exists();
                
                if (($status === 'active' || $status === 'trialing') && !$hasPayment) {
                    $this->createPaymentFromRazorpay($subscription, $payment);
                } elseif ($oldStatus === 'pending' && ($status === 'active' || $status === 'trialing')) {
                    // Also create if status changed from pending to active/trialing
                    $this->createPaymentFromRazorpay($subscription, $payment);
                }

                    'subscription_id' => $subscription->id,
                    'payment_id' => $paymentId,
                    'status' => $status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify Razorpay subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Refresh subscription status from payment gateway.
     */
    public function refresh(Subscription $subscription): RedirectResponse
    {
        $user = auth()->user();

        // Verify subscription belongs to user
        if ($subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        try {
            if ($subscription->gateway === 'stripe') {
                $this->verifyStripeSubscription($subscription);
            } elseif ($subscription->gateway === 'razorpay') {
                // For Razorpay, we'd need payment ID to verify
                // For now, just return with message
                return redirect()->route('member.subscription.index')
                    ->with('info', 'Razorpay status refresh requires payment ID. Please contact support if status is incorrect.');
            }

            $subscription->refresh();

            // If subscription is active/trialing but has no payment record, create one
            if (($subscription->status === 'active' || $subscription->status === 'trialing')) {
                $hasPayment = Payment::where('subscription_id', $subscription->id)->exists();
                if (!$hasPayment) {
                    $this->createPaymentFromSubscription($subscription);
                }
            }

            if ($subscription->status !== 'pending') {
                return redirect()->route('member.subscription.index')
                    ->with('success', 'Subscription status updated successfully.');
            } else {
                return redirect()->route('member.subscription.index')
                    ->with('info', 'Subscription status is still pending. Payment may still be processing. Webhooks will update the status automatically.');
            }
        } catch (\Exception $e) {
            Log::error('Subscription refresh failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('member.subscription.index')
                ->with('error', 'Failed to refresh subscription status. Please try again later.');
        }
    }

    /**
     * Map Stripe status to our status.
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match($stripeStatus) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled', 'unpaid' => 'canceled',
            default => 'pending',
        };
    }

    /**
     * Create payment record from Stripe invoice.
     */
    protected function createPaymentFromStripeInvoice(Subscription $subscription, $invoice): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            // Check if payment already exists for this transaction
            $existingPayment = Payment::where('subscription_id', $subscription->id)
                ->where('transaction_id', $invoice->payment_intent ?? $invoice->id)
                ->first();

            if ($existingPayment) {
                return; // Payment already exists
            }

            $amount = $invoice->amount_paid / 100; // Convert from cents
            $paymentMethod = $this->mapStripePaymentMethod($invoice->payment_intent);

            Payment::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $invoice->payment_intent ?? $invoice->id,
                'status' => $invoice->paid ? 'completed' : 'pending',
                'payment_details' => [
                    'invoice_id' => $invoice->id,
                    'payment_intent_id' => $invoice->payment_intent,
                    'currency' => $invoice->currency,
                    'gateway' => 'stripe',
                ],
                'discount_amount' => 0,
                'final_amount' => $amount,
                'paid_at' => $invoice->paid ? now() : null,
            ]);

                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from Stripe invoice', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create payment record from Stripe payment/setup intent.
     */
    protected function createPaymentFromStripeIntent(Subscription $subscription, $intent, string $intentType): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            // Check if payment already exists for this transaction
            $existingPayment = Payment::where('subscription_id', $subscription->id)
                ->where('transaction_id', $intent->id)
                ->first();

            if ($existingPayment) {
                return; // Payment already exists
            }

            // For setup intents (trial subscriptions), amount is 0
            // For payment intents, get amount from intent
            $amount = 0;
            if ($intentType === 'payment_intent' && isset($intent->amount)) {
                $amount = $intent->amount / 100; // Convert from cents
            } elseif ($intentType === 'payment_intent' && isset($intent->amount_received)) {
                $amount = $intent->amount_received / 100;
            } else {
                // For setup intents or if no amount, use plan price
                $amount = $plan->price;
            }

            $paymentMethod = $this->mapStripePaymentMethod($intent->id, $intent->payment_method_types ?? []);

            Payment::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $intent->id,
                'status' => $intent->status === 'succeeded' ? 'completed' : 'pending',
                'payment_details' => [
                    'intent_id' => $intent->id,
                    'intent_type' => $intentType,
                    'currency' => $intent->currency ?? 'usd',
                    'gateway' => 'stripe',
                ],
                'discount_amount' => 0,
                'final_amount' => $amount,
                'paid_at' => $intent->status === 'succeeded' ? now() : null,
            ]);

                'subscription_id' => $subscription->id,
                'intent_id' => $intent->id,
                'intent_type' => $intentType,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from Stripe intent', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create payment record from Razorpay payment.
     */
    protected function createPaymentFromRazorpay(Subscription $subscription, $payment): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            // Check if payment already exists for this transaction
            $existingPayment = Payment::where('subscription_id', $subscription->id)
                ->where('transaction_id', $payment->id)
                ->first();

            if ($existingPayment) {
                return; // Payment already exists
            }

            $amount = $payment->amount / 100; // Convert from paise to rupees
            $paymentMethod = $this->mapRazorpayPaymentMethod($payment->method ?? 'other');

            Payment::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $payment->id,
                'status' => ($payment->status === 'captured' || $payment->status === 'authorized') ? 'completed' : 'pending',
                'payment_details' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id ?? null,
                    'method' => $payment->method ?? null,
                    'currency' => $payment->currency ?? 'INR',
                    'gateway' => 'razorpay',
                ],
                'discount_amount' => 0,
                'final_amount' => $amount,
                'paid_at' => ($payment->status === 'captured' || $payment->status === 'authorized') ? now() : null,
            ]);

                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from Razorpay', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map Stripe payment method to our payment method enum.
     */
    protected function mapStripePaymentMethod(?string $paymentIntentId = null, array $paymentMethodTypes = []): string
    {
        if (empty($paymentMethodTypes)) {
            return 'credit_card'; // Default
        }

        $method = $paymentMethodTypes[0] ?? 'card';
        
        return match($method) {
            'card' => 'credit_card',
            'upi' => 'upi',
            default => 'other',
        };
    }

    /**
     * Map Razorpay payment method to our payment method enum.
     */
    protected function mapRazorpayPaymentMethod(?string $method): string
    {
        if (!$method) {
            return 'other';
        }

        return match(strtolower($method)) {
            'card' => 'credit_card',
            'upi' => 'upi',
            'netbanking' => 'bank_transfer',
            'wallet' => 'other',
            default => 'other',
        };
    }

    /**
     * Create payment record from subscription data (for existing active subscriptions without payments).
     */
    protected function createPaymentFromSubscription(Subscription $subscription): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            // Check if payment already exists
            $existingPayment = Payment::where('subscription_id', $subscription->id)->first();
            if ($existingPayment) {
                return;
            }

            // Use subscription started_at or created_at as paid_at
            $paidAt = $subscription->started_at ?? $subscription->created_at;

            Payment::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'payment_method' => $subscription->gateway === 'stripe' ? 'credit_card' : ($subscription->gateway === 'razorpay' ? 'other' : 'other'),
                'transaction_id' => $subscription->gateway_subscription_id ?? 'sub_' . $subscription->id,
                'status' => ($subscription->status === 'active' || $subscription->status === 'trialing') ? 'completed' : 'pending',
                'payment_details' => [
                    'subscription_id' => $subscription->gateway_subscription_id,
                    'gateway' => $subscription->gateway,
                    'created_from' => 'subscription_backfill',
                ],
                'discount_amount' => 0,
                'final_amount' => $plan->price,
                'paid_at' => $paidAt,
            ]);

                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create payment record from subscription verification (when payment succeeded but record missing).
     */
    protected function createPaymentFromSubscriptionVerification(Subscription $subscription, ?string $paymentIntentId = null): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            // Check if payment already exists
            $existingPayment = Payment::where('subscription_id', $subscription->id)->first();
            if ($existingPayment) {
                return;
            }

            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $stripe = new \Stripe\StripeClient($paymentSettings->stripe_secret_key);

            // Try to get payment intent from Stripe
            $paymentIntent = null;
            $amount = $plan->price;
            $paidAt = $subscription->started_at ?? $subscription->created_at;

            if ($paymentIntentId) {
                // Use provided payment intent ID
                try {
                    $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
                    if ($paymentIntent->status === 'succeeded') {
                        $amount = $paymentIntent->amount / 100;
                        $paidAt = date('Y-m-d H:i:s', $paymentIntent->created);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve payment intent', ['payment_intent_id' => $paymentIntentId]);
                }
            }

            // If no payment intent, try to get from subscription's latest invoice
            if (!$paymentIntent && $subscription->gateway_subscription_id) {
                try {
                    $stripeSubscription = $stripe->subscriptions->retrieve($subscription->gateway_subscription_id);
                    if (isset($stripeSubscription->latest_invoice)) {
                        $invoice = is_string($stripeSubscription->latest_invoice)
                            ? $stripe->invoices->retrieve($stripeSubscription->latest_invoice)
                            : $stripeSubscription->latest_invoice;
                        
                        if ($invoice->paid && isset($invoice->payment_intent)) {
                            $paymentIntent = $stripe->paymentIntents->retrieve($invoice->payment_intent);
                            $amount = $invoice->amount_paid / 100;
                            $paidAt = date('Y-m-d H:i:s', $invoice->created);
                            $paymentIntentId = $invoice->payment_intent;
                        } elseif ($invoice->paid) {
                            $amount = $invoice->amount_paid / 100;
                            $paidAt = date('Y-m-d H:i:s', $invoice->created);
                            $paymentIntentId = $invoice->id;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve subscription invoice', [
                        'subscription_id' => $subscription->gateway_subscription_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Build payment data array
            $paymentData = [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_details' => [
                    'payment_intent_id' => $paymentIntentId,
                    'gateway' => 'stripe',
                    'created_from' => 'subscription_verification',
                ],
                'discount_amount' => 0,
                'paid_at' => $paidAt,
            ];

            // Add accounting system required columns if they exist
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'date')) {
                $paymentData['date'] = date('Y-m-d', strtotime($paidAt));
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'is_credit')) {
                $paymentData['is_credit'] = 0;
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'currency_code')) {
                $paymentData['currency_code'] = 'USD';
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'exchange_rate')) {
                $paymentData['exchange_rate'] = 1.000000;
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'inverse')) {
                $paymentData['inverse'] = 0;
            }

            // Only add columns if they exist
            if (Payment::hasTransactionIdColumn() && $paymentIntentId) {
                $paymentData['transaction_id'] = $paymentIntentId;
            }
            
            if (Payment::hasPaymentMethodColumn()) {
                $paymentMethod = 'credit_card'; // Default for Stripe
                if ($paymentIntent && isset($paymentIntent->payment_method_types)) {
                    $method = $paymentIntent->payment_method_types[0] ?? 'card';
                    $paymentMethod = match($method) {
                        'card' => 'credit_card',
                        'upi' => 'upi',
                        default => 'other',
                    };
                }
                $paymentData['payment_method'] = $paymentMethod;
            }
            
            if (Payment::hasStatusColumn()) {
                $paymentData['status'] = 'completed';
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'final_amount')) {
                $paymentData['final_amount'] = $amount;
            }

            Payment::create($paymentData);

                'subscription_id' => $subscription->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from subscription verification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

