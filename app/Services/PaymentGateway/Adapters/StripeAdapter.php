<?php

namespace App\Services\PaymentGateway\Adapters;

use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeAdapter
{
    protected StripeClient $stripe;
    protected PaymentSetting $settings;

    public function __construct(PaymentSetting $settings)
    {
        $this->settings = $settings;
        $this->stripe = new StripeClient($settings->stripe_secret_key);
    }

    /**
     * Create Stripe subscription.
     */
    public function createSubscription(User $user, SubscriptionPlan $plan, bool $hasTrial, int $trialDays): array
    {
        try {
            // Get or create Stripe customer
            $customer = $this->getOrCreateCustomer($user);

            if ($hasTrial && $trialDays > 0) {
                // Create subscription with trial period
                return $this->createTrialSubscription($user, $plan, $customer, $trialDays);
            } else {
                // Create subscription with immediate charge
                return $this->createPaidSubscription($user, $plan, $customer);
            }
        } catch (\Exception $e) {
            Log::error('Stripe subscription creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or create Stripe customer.
     */
    protected function getOrCreateCustomer(User $user): \Stripe\Customer
    {
        // Check if user already has a Stripe customer ID stored
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->where('gateway', 'stripe')
            ->whereNotNull('gateway_customer_id')
            ->first();

        if ($existingSubscription && $existingSubscription->gateway_customer_id) {
            try {
                return $this->stripe->customers->retrieve($existingSubscription->gateway_customer_id);
            } catch (\Exception $e) {
                // Customer doesn't exist in Stripe, create new one
            }
        }

        // Create new customer
        return $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);
    }

    /**
     * Create subscription with trial period.
     */
    protected function createTrialSubscription(User $user, SubscriptionPlan $plan, \Stripe\Customer $customer, int $trialDays): array
    {
        // Create Stripe Price if not exists
        $priceId = $plan->stripe_price_id;
        
        if (!$priceId) {
            $price = $this->stripe->prices->create([
                'unit_amount' => (int)($plan->price * 100), // Convert to cents
                'currency' => 'usd',
                'recurring' => [
                    'interval' => $this->getStripeInterval($plan->duration_type),
                ],
                'product_data' => [
                    'name' => $plan->plan_name,
                ],
            ]);
            $priceId = $price->id;
        }

        // Create subscription with trial
        // Stripe will automatically create a pending_setup_intent for trial subscriptions
        $subscription = $this->stripe->subscriptions->create([
            'customer' => $customer->id,
            'items' => [
                ['price' => $priceId],
            ],
            'trial_period_days' => $trialDays,
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'payment_method_types' => ['card'],
            ],
            'expand' => ['latest_invoice.payment_intent', 'pending_setup_intent'],
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        // Debug: Log subscription object structure
        Log::info('Trial subscription created', [
            'subscription_id' => $subscription->id,
            'has_pending_setup_intent' => isset($subscription->pending_setup_intent),
            'pending_setup_intent_id' => $subscription->pending_setup_intent ?? null,
            'has_latest_invoice' => isset($subscription->latest_invoice),
            'latest_invoice_id' => is_string($subscription->latest_invoice) ? $subscription->latest_invoice : ($subscription->latest_invoice->id ?? null),
        ]);

        // Get client secret for Payment Element
        // For trial subscriptions, use SetupIntent client_secret
        $clientSecret = null;
        
        try {
            // For trial subscriptions, check for pending_setup_intent first
            if (isset($subscription->pending_setup_intent)) {
                $setupIntentObj = is_string($subscription->pending_setup_intent)
                    ? $this->stripe->setupIntents->retrieve($subscription->pending_setup_intent)
                    : $subscription->pending_setup_intent;
                
                $clientSecret = $setupIntentObj->client_secret ?? null;
                Log::info('Using SetupIntent client_secret for trial subscription', [
                    'setup_intent_id' => $setupIntentObj->id ?? null,
                    'client_secret_preview' => $clientSecret ? substr($clientSecret, 0, 20) . '...' : 'NULL',
                ]);
            } else {
                Log::warning('No pending_setup_intent in subscription object', [
                    'subscription_id' => $subscription->id,
                    'subscription_keys' => array_keys((array)$subscription),
                ]);
            }
            
            // If no setup intent, check for payment intent in invoice
            if (!$clientSecret && isset($subscription->latest_invoice) && $subscription->latest_invoice) {
                $invoice = is_string($subscription->latest_invoice) 
                    ? $this->stripe->invoices->retrieve($subscription->latest_invoice, ['expand' => ['payment_intent']])
                    : $subscription->latest_invoice;
                
                if (isset($invoice->payment_intent)) {
                    $paymentIntent = is_string($invoice->payment_intent)
                        ? $this->stripe->paymentIntents->retrieve($invoice->payment_intent)
                        : $invoice->payment_intent;
                    
                    $clientSecret = $paymentIntent->client_secret ?? null;
                    Log::info('Using PaymentIntent client_secret for trial subscription', [
                        'payment_intent_id' => $paymentIntent->id ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client secret from Stripe trial subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }

        // If still no client secret, retrieve the subscription with all expands
        if (!$clientSecret) {
            try {
                Log::info('Retrieving subscription with expanded objects', [
                    'subscription_id' => $subscription->id,
                ]);
                
                $fullSubscription = $this->stripe->subscriptions->retrieve($subscription->id, [
                    'expand' => ['latest_invoice.payment_intent', 'pending_setup_intent'],
                ]);
                
                Log::info('Retrieved subscription', [
                    'has_pending_setup_intent' => isset($fullSubscription->pending_setup_intent),
                    'pending_setup_intent_type' => isset($fullSubscription->pending_setup_intent) ? gettype($fullSubscription->pending_setup_intent) : 'not set',
                    'has_latest_invoice' => isset($fullSubscription->latest_invoice),
                ]);
                
                // Try setup intent first
                if (isset($fullSubscription->pending_setup_intent)) {
                    $setupIntentObj = is_string($fullSubscription->pending_setup_intent)
                        ? $this->stripe->setupIntents->retrieve($fullSubscription->pending_setup_intent)
                        : $fullSubscription->pending_setup_intent;
                    $clientSecret = $setupIntentObj->client_secret ?? null;
                    
                    Log::info('Retrieved SetupIntent', [
                        'setup_intent_id' => $setupIntentObj->id ?? null,
                        'has_client_secret' => !empty($clientSecret),
                    ]);
                }
                
                // Then try payment intent
                if (!$clientSecret && isset($fullSubscription->latest_invoice)) {
                    $invoice = is_string($fullSubscription->latest_invoice)
                        ? $this->stripe->invoices->retrieve($fullSubscription->latest_invoice, ['expand' => ['payment_intent']])
                        : $fullSubscription->latest_invoice;
                    
                    if (isset($invoice->payment_intent)) {
                        $paymentIntent = is_string($invoice->payment_intent)
                            ? $this->stripe->paymentIntents->retrieve($invoice->payment_intent)
                            : $invoice->payment_intent;
                        $clientSecret = $paymentIntent->client_secret ?? null;
                        
                        Log::info('Retrieved PaymentIntent', [
                            'payment_intent_id' => $paymentIntent->id ?? null,
                            'has_client_secret' => !empty($clientSecret),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to retrieve client secret from expanded trial subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if (!$clientSecret) {
            Log::warning('No client_secret found for Stripe trial subscription', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'has_pending_setup_intent' => isset($subscription->pending_setup_intent),
                'has_latest_invoice' => isset($subscription->latest_invoice),
            ]);
        }

        return [
            'subscription_id' => $subscription->id,
            'customer_id' => $customer->id,
            'client_secret' => $clientSecret,
            'status' => 'trialing',
            'trial_end' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
        ];
    }

    /**
     * Create subscription with immediate charge (using Payment Element on page).
     */
    protected function createPaidSubscription(User $user, SubscriptionPlan $plan, \Stripe\Customer $customer): array
    {
        // Create Stripe Price if not exists
        $priceId = $plan->stripe_price_id;
        
        if (!$priceId) {
            $price = $this->stripe->prices->create([
                'unit_amount' => (int)($plan->price * 100),
                'currency' => 'usd',
                'recurring' => [
                    'interval' => $this->getStripeInterval($plan->duration_type),
                ],
                'product_data' => [
                    'name' => $plan->plan_name,
                ],
            ]);
            $priceId = $price->id;
        }

        // Create subscription with payment_behavior: 'default_incomplete' to get client secret
        $subscription = $this->stripe->subscriptions->create([
            'customer' => $customer->id,
            'items' => [
                ['price' => $priceId],
            ],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'payment_method_types' => ['card'],
            ],
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        // Debug: Log subscription object structure
        Log::info('Paid subscription created', [
            'subscription_id' => $subscription->id,
            'has_latest_invoice' => isset($subscription->latest_invoice),
            'latest_invoice_id' => is_string($subscription->latest_invoice) ? $subscription->latest_invoice : ($subscription->latest_invoice->id ?? null),
            'has_payment_intent' => isset($subscription->latest_invoice) && isset($subscription->latest_invoice->payment_intent),
        ]);

        // Get client secret for Payment Element
        $clientSecret = null;
        
        try {
            // First, try to get it directly from the expanded invoice
            if (isset($subscription->latest_invoice) && $subscription->latest_invoice) {
                $invoice = is_string($subscription->latest_invoice) 
                    ? $this->stripe->invoices->retrieve($subscription->latest_invoice, ['expand' => ['payment_intent']])
                    : $subscription->latest_invoice;
                
                Log::info('Retrieved invoice for paid subscription', [
                    'invoice_id' => $invoice->id ?? null,
                    'has_payment_intent' => isset($invoice->payment_intent),
                    'payment_intent_type' => isset($invoice->payment_intent) ? gettype($invoice->payment_intent) : 'not set',
                ]);
                
                if (isset($invoice->payment_intent)) {
                    $paymentIntent = is_string($invoice->payment_intent)
                        ? $this->stripe->paymentIntents->retrieve($invoice->payment_intent)
                        : $invoice->payment_intent;
                    
                    $clientSecret = $paymentIntent->client_secret ?? null;
                    
                    Log::info('Retrieved PaymentIntent for paid subscription', [
                        'payment_intent_id' => $paymentIntent->id ?? null,
                        'has_client_secret' => !empty($clientSecret),
                        'client_secret_preview' => $clientSecret ? substr($clientSecret, 0, 20) . '...' : 'NULL',
                    ]);
                } else {
                    Log::warning('No payment_intent in invoice', [
                        'invoice_id' => $invoice->id ?? null,
                        'invoice_status' => $invoice->status ?? null,
                    ]);
                }
            } else {
                Log::warning('No latest_invoice in subscription', [
                    'subscription_id' => $subscription->id,
                    'subscription_status' => $subscription->status ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client secret from Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // If still no client secret, try to get it from the subscription directly
        if (!$clientSecret) {
            try {
                Log::info('Retrieving subscription with expanded invoice for paid subscription', [
                    'subscription_id' => $subscription->id,
                ]);
                
                // Retrieve the subscription with expanded invoice
                $fullSubscription = $this->stripe->subscriptions->retrieve($subscription->id, [
                    'expand' => ['latest_invoice.payment_intent'],
                ]);
                
                Log::info('Retrieved full subscription', [
                    'has_latest_invoice' => isset($fullSubscription->latest_invoice),
                    'latest_invoice_type' => isset($fullSubscription->latest_invoice) ? gettype($fullSubscription->latest_invoice) : 'not set',
                ]);
                
                if (isset($fullSubscription->latest_invoice)) {
                    $invoice = is_string($fullSubscription->latest_invoice)
                        ? $this->stripe->invoices->retrieve($fullSubscription->latest_invoice, ['expand' => ['payment_intent']])
                        : $fullSubscription->latest_invoice;
                    
                    Log::info('Processing invoice', [
                        'invoice_id' => $invoice->id ?? null,
                        'invoice_status' => $invoice->status ?? null,
                        'has_payment_intent' => isset($invoice->payment_intent),
                    ]);
                    
                    if (isset($invoice->payment_intent)) {
                        $paymentIntent = is_string($invoice->payment_intent)
                            ? $this->stripe->paymentIntents->retrieve($invoice->payment_intent)
                            : $invoice->payment_intent;
                        
                        $clientSecret = $paymentIntent->client_secret ?? null;
                        
                        Log::info('Retrieved PaymentIntent from expanded subscription', [
                            'payment_intent_id' => $paymentIntent->id ?? null,
                            'payment_intent_status' => $paymentIntent->status ?? null,
                            'has_client_secret' => !empty($clientSecret),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to retrieve client secret from expanded subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if (!$clientSecret) {
            Log::warning('No client_secret found for Stripe paid subscription', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'has_latest_invoice' => isset($subscription->latest_invoice),
            ]);
            
            // Last resort: Try to create a payment intent manually for the subscription
            try {
                Log::info('Attempting to create PaymentIntent manually for subscription', [
                    'subscription_id' => $subscription->id,
                ]);
                
                // Get the invoice amount
                if (isset($subscription->latest_invoice)) {
                    $invoice = is_string($subscription->latest_invoice)
                        ? $this->stripe->invoices->retrieve($subscription->latest_invoice)
                        : $subscription->latest_invoice;
                    
                    if ($invoice && isset($invoice->amount_due)) {
                        // Create a payment intent for the subscription
                        $paymentIntent = $this->stripe->paymentIntents->create([
                            'amount' => $invoice->amount_due,
                            'currency' => $invoice->currency ?? 'usd',
                            'customer' => $customer->id,
                            'setup_future_usage' => 'off_session',
                            'metadata' => [
                                'subscription_id' => $subscription->id,
                                'user_id' => $user->id,
                                'plan_id' => $plan->id,
                            ],
                        ]);
                        
                        $clientSecret = $paymentIntent->client_secret;
                        
                        Log::info('Created PaymentIntent manually', [
                            'payment_intent_id' => $paymentIntent->id,
                            'has_client_secret' => !empty($clientSecret),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to create PaymentIntent manually', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'subscription_id' => $subscription->id,
            'customer_id' => $customer->id,
            'client_secret' => $clientSecret,
            'status' => 'pending',
        ];
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Subscription $subscription): bool
    {
        try {
            if (!$subscription->gateway_subscription_id) {
                return false;
            }

            $stripeSubscription = $this->stripe->subscriptions->retrieve($subscription->gateway_subscription_id);
            
            // Cancel at period end
            $this->stripe->subscriptions->update($subscription->gateway_subscription_id, [
                'cancel_at_period_end' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Stripe subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle Stripe webhook.
     */
    public function handleWebhook(array $payload): bool
    {
        $eventType = $payload['type'] ?? null;
        $data = $payload['data']['object'] ?? null;

        if (!$eventType || !$data) {
            return false;
        }

        try {
            switch ($eventType) {
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdate($data);
                    break;
                
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($data);
                    break;
                
                case 'invoice.payment_succeeded':
                    $this->handlePaymentSucceeded($data);
                    break;
                
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($data);
                    break;
                
                case 'setup_intent.succeeded':
                    $this->handleSetupIntentSucceeded($data);
                    break;
                
                case 'invoice.payment_failed':
                    $this->handlePaymentFailed($data);
                    break;
                
                case 'customer.subscription.trial_will_end':
                    $this->handleTrialWillEnd($data);
                    break;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle subscription update.
     */
    protected function handleSubscriptionUpdate(array $data): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $data['id'])->first();
        
        if (!$subscription) {
            return;
        }

        $status = $this->mapStripeStatus($data['status']);
        
        $subscription->update([
            'status' => $status,
            'trial_end_at' => isset($data['trial_end']) ? date('Y-m-d H:i:s', $data['trial_end']) : null,
            'next_billing_at' => isset($data['current_period_end']) ? date('Y-m-d H:i:s', $data['current_period_end']) : null,
            'started_at' => isset($data['start_date']) ? date('Y-m-d H:i:s', $data['start_date']) : null,
        ]);
    }

    /**
     * Handle subscription deleted.
     */
    protected function handleSubscriptionDeleted(array $data): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $data['id'])->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);
        }
    }

    /**
     * Handle payment succeeded.
     */
    protected function handlePaymentSucceeded(array $data): void
    {
        $subscriptionId = $data['subscription'] ?? null;
        
        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)->first();
        
        if ($subscription) {
            $oldStatus = $subscription->status;
            
            // Update status based on current state
            if ($subscription->status === 'pending') {
                // Payment succeeded, activate subscription
                $plan = $subscription->subscriptionPlan;
                $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                
                $subscription->update([
                    'status' => $status,
                    'started_at' => now(),
                ]);
                
                // Create payment record
                $this->createPaymentFromInvoiceData($subscription, $data);
                
                Log::info('Subscription activated via invoice.payment_succeeded webhook', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscriptionId,
                    'status' => $status,
                ]);
            } elseif ($subscription->status === 'trialing') {
                // Trial ended, payment succeeded, activate
                $subscription->update([
                    'status' => 'active',
                ]);
                
                // Create payment record for renewal
                $this->createPaymentFromInvoiceData($subscription, $data);
                
                Log::info('Subscription activated from trialing via invoice.payment_succeeded webhook', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscriptionId,
                ]);
            }
        }
    }

    /**
     * Handle payment intent succeeded.
     */
    protected function handlePaymentIntentSucceeded(array $data): void
    {
        $paymentIntentId = $data['id'] ?? null;
        $subscriptionId = $data['metadata']['subscription_id'] ?? null;
        
        if (!$paymentIntentId) {
            return;
        }

        // Find subscription by payment intent in metadata or by subscription ID
        $subscription = null;
        
        if ($subscriptionId) {
            $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)->first();
        }
        
        // If not found, try to find by payment intent in metadata
        if (!$subscription) {
            $subscription = Subscription::where('metadata->payment_intent_id', $paymentIntentId)
                ->orWhereJsonContains('metadata->payment_intent_id', $paymentIntentId)
                ->first();
        }
        
        // If still not found, find most recent pending subscription for the customer
        if (!$subscription && isset($data['customer'])) {
            $subscription = Subscription::where('gateway_customer_id', $data['customer'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if ($subscription) {
            // Check if payment already exists
            $hasPayment = false;
            if (Payment::hasTransactionIdColumn()) {
                $hasPayment = Payment::where('subscription_id', $subscription->id)
                    ->where('transaction_id', $paymentIntentId)
                    ->exists();
            } else {
                // Fallback: check by subscription_id and amount
                $amount = ($data['amount'] ?? $data['amount_received'] ?? 0) / 100;
                $hasPayment = Payment::where('subscription_id', $subscription->id)
                    ->where('amount', $amount)
                    ->exists();
            }
            
            if ($subscription->status === 'pending') {
                $plan = $subscription->subscriptionPlan;
                $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
                
                $subscription->update([
                    'status' => $status,
                    'started_at' => now(),
                ]);
                
                // Create payment record if it doesn't exist
                if (!$hasPayment) {
                    $this->createPaymentFromPaymentIntent($subscription, $data);
                }
                
                Log::info('Subscription activated via payment_intent.succeeded webhook', [
                    'subscription_id' => $subscription->id,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $status,
                ]);
            } elseif (($subscription->status === 'active' || $subscription->status === 'trialing') && !$hasPayment) {
                // Create payment record for active subscriptions that don't have payments
                $this->createPaymentFromPaymentIntent($subscription, $data);
                
                Log::info('Payment record created for active subscription via payment_intent.succeeded webhook', [
                    'subscription_id' => $subscription->id,
                    'payment_intent_id' => $paymentIntentId,
                ]);
            }
        } else {
            // If subscription not found, try to find by payment intent in invoices
            // This handles cases where payment succeeded but subscription wasn't linked
            try {
                $paymentSettings = PaymentSetting::getSettings();
                $stripe = new \Stripe\StripeClient($paymentSettings->stripe_secret_key);
                $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
                
                if (isset($paymentIntent->invoice)) {
                    $invoice = $stripe->invoices->retrieve($paymentIntent->invoice);
                    if (isset($invoice->subscription)) {
                        $subscription = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
                        if ($subscription) {
                            $hasPayment = false;
                            if (Payment::hasTransactionIdColumn()) {
                                $hasPayment = Payment::where('subscription_id', $subscription->id)
                                    ->where('transaction_id', $paymentIntentId)
                                    ->exists();
                            } else {
                                // Fallback: check by subscription_id and amount
                                $amount = ($data['amount'] ?? $data['amount_received'] ?? 0) / 100;
                                $hasPayment = Payment::where('subscription_id', $subscription->id)
                                    ->where('amount', $amount)
                                    ->exists();
                            }
                            
                            if (!$hasPayment) {
                                $this->createPaymentFromPaymentIntent($subscription, $data);
                                
                                Log::info('Payment record created from invoice lookup via payment_intent.succeeded webhook', [
                                    'subscription_id' => $subscription->id,
                                    'payment_intent_id' => $paymentIntentId,
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to lookup subscription from payment intent invoice', [
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle setup intent succeeded.
     */
    protected function handleSetupIntentSucceeded(array $data): void
    {
        $setupIntentId = $data['id'] ?? null;
        $subscriptionId = $data['metadata']['subscription_id'] ?? null;
        
        if (!$setupIntentId) {
            return;
        }

        // Find subscription by setup intent
        $subscription = null;
        
        if ($subscriptionId) {
            $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)->first();
        }
        
        // If not found, try to find by setup intent in metadata
        if (!$subscription) {
            $subscription = Subscription::where('metadata->setup_intent_id', $setupIntentId)
                ->orWhereJsonContains('metadata->setup_intent_id', $setupIntentId)
                ->first();
        }
        
        // If still not found, find most recent pending subscription for the customer
        if (!$subscription && isset($data['customer'])) {
            $subscription = Subscription::where('gateway_customer_id', $data['customer'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if ($subscription && $subscription->status === 'pending') {
            // Setup intent succeeded means payment method is saved, subscription should be trialing
            $plan = $subscription->subscriptionPlan;
            $status = ($plan && $plan->hasTrial()) ? 'trialing' : 'active';
            
            $subscription->update([
                'status' => $status,
                'started_at' => now(),
            ]);
            
            Log::info('Subscription activated via setup_intent.succeeded webhook', [
                'subscription_id' => $subscription->id,
                'setup_intent_id' => $setupIntentId,
                'status' => $status,
            ]);
        }
    }

    /**
     * Handle payment failed.
     */
    protected function handlePaymentFailed(array $data): void
    {
        $subscriptionId = $data['subscription'] ?? null;
        
        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'past_due',
            ]);
        }
    }

    /**
     * Handle trial will end.
     */
    protected function handleTrialWillEnd(array $data): void
    {
        // You can send notification emails here
        Log::info('Trial will end', ['subscription_id' => $data['id']]);
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
     * Create payment record from invoice data.
     */
    protected function createPaymentFromInvoiceData(Subscription $subscription, array $data): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            $invoiceId = $data['id'] ?? null;
            $paymentIntentId = $data['payment_intent'] ?? null;
            $transactionId = $paymentIntentId ?? $invoiceId;

            // Check if payment already exists
            $existingPayment = null;
            if (Payment::hasTransactionIdColumn()) {
                $existingPayment = Payment::where('subscription_id', $subscription->id)
                    ->where('transaction_id', $transactionId)
                    ->first();
            } else {
                // Fallback: check by subscription_id and amount
                $amount = ($data['amount_paid'] ?? $data['amount_due'] ?? 0) / 100;
                $existingPayment = Payment::where('subscription_id', $subscription->id)
                    ->where('amount', $amount)
                    ->first();
            }

            if ($existingPayment) {
                return;
            }

            $amount = ($data['amount_paid'] ?? $data['amount_due'] ?? 0) / 100; // Convert from cents
            $paymentMethod = $this->mapStripePaymentMethodToEnum($data['payment_method_types'] ?? ['card']);
            $isPaid = $data['paid'] ?? false;
            $paidAt = $isPaid ? (isset($data['created']) ? date('Y-m-d H:i:s', $data['created']) : now()) : null;

            // Build payment data array
            $paymentData = [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_details' => [
                    'invoice_id' => $invoiceId,
                    'payment_intent_id' => $paymentIntentId,
                    'currency' => $data['currency'] ?? 'usd',
                    'gateway' => 'stripe',
                ],
                'discount_amount' => 0,
                'paid_at' => $paidAt,
            ];

            // Add accounting system required columns if they exist
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'date')) {
                $paymentData['date'] = isset($data['created']) ? date('Y-m-d', $data['created']) : date('Y-m-d');
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'is_credit')) {
                $paymentData['is_credit'] = 0; // Payment is a debit (money coming in)
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'currency_code')) {
                $paymentData['currency_code'] = strtoupper($data['currency'] ?? 'USD');
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'exchange_rate')) {
                $paymentData['exchange_rate'] = 1.000000;
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'inverse')) {
                $paymentData['inverse'] = 0;
            }

            // Only add columns if they exist
            if (Payment::hasTransactionIdColumn()) {
                $paymentData['transaction_id'] = $transactionId;
            }
            
            if (Payment::hasPaymentMethodColumn()) {
                $paymentData['payment_method'] = $paymentMethod;
            }
            
            if (Payment::hasStatusColumn()) {
                $paymentData['status'] = $isPaid ? 'completed' : 'pending';
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'final_amount')) {
                $paymentData['final_amount'] = $amount;
            }

            Payment::create($paymentData);

            Log::info('Payment record created from invoice webhook', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoiceId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from invoice data', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create payment record from payment intent data.
     */
    protected function createPaymentFromPaymentIntent(Subscription $subscription, array $data): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            $paymentIntentId = $data['id'] ?? null;
            if (!$paymentIntentId) {
                return;
            }

            // Check if payment already exists
            $existingPayment = null;
            if (Payment::hasTransactionIdColumn()) {
                $existingPayment = Payment::where('subscription_id', $subscription->id)
                    ->where('transaction_id', $paymentIntentId)
                    ->first();
            } else {
                // Fallback: check by subscription_id and amount
                $amount = ($data['amount'] ?? $data['amount_received'] ?? 0) / 100;
                $existingPayment = Payment::where('subscription_id', $subscription->id)
                    ->where('amount', $amount)
                    ->first();
            }

            if ($existingPayment) {
                return;
            }

            $amount = ($data['amount'] ?? $data['amount_received'] ?? 0) / 100; // Convert from cents
            $paymentMethod = $this->mapStripePaymentMethodToEnum($data['payment_method_types'] ?? ['card']);
            $isSucceeded = ($data['status'] ?? '') === 'succeeded';
            $paidAt = $isSucceeded ? (isset($data['created']) ? date('Y-m-d H:i:s', $data['created']) : now()) : null;

            // Build payment data array
            $paymentData = [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_details' => [
                    'payment_intent_id' => $paymentIntentId,
                    'currency' => $data['currency'] ?? 'usd',
                    'gateway' => 'stripe',
                ],
                'discount_amount' => 0,
                'paid_at' => $paidAt,
            ];

            // Add accounting system required columns if they exist
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'date')) {
                $paymentData['date'] = isset($data['created']) ? date('Y-m-d', $data['created']) : date('Y-m-d');
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'is_credit')) {
                $paymentData['is_credit'] = 0; // Payment is a debit (money coming in)
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'currency_code')) {
                $paymentData['currency_code'] = strtoupper($data['currency'] ?? 'USD');
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'exchange_rate')) {
                $paymentData['exchange_rate'] = 1.000000;
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'inverse')) {
                $paymentData['inverse'] = 0;
            }

            // Only add columns if they exist
            if (Payment::hasTransactionIdColumn()) {
                $paymentData['transaction_id'] = $paymentIntentId;
            }
            
            if (Payment::hasPaymentMethodColumn()) {
                $paymentData['payment_method'] = $paymentMethod;
            }
            
            if (Payment::hasStatusColumn()) {
                $paymentData['status'] = $isSucceeded ? 'completed' : 'pending';
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'final_amount')) {
                $paymentData['final_amount'] = $amount;
            }

            Payment::create($paymentData);

            Log::info('Payment record created from payment intent webhook', [
                'subscription_id' => $subscription->id,
                'payment_intent_id' => $paymentIntentId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from payment intent', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map Stripe payment method types to our payment method enum.
     */
    protected function mapStripePaymentMethodToEnum(array $paymentMethodTypes): string
    {
        if (empty($paymentMethodTypes)) {
            return 'credit_card';
        }

        $method = $paymentMethodTypes[0] ?? 'card';
        
        return match($method) {
            'card' => 'credit_card',
            'upi' => 'upi',
            default => 'other',
        };
    }

    /**
     * Get Stripe interval from duration type.
     */
    protected function getStripeInterval(string $durationType): string
    {
        return match($durationType) {
            'daily' => 'day',
            'weekly' => 'week',
            'monthly' => 'month',
            'yearly' => 'year',
            default => 'month',
        };
    }
}

