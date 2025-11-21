<?php

namespace App\Services\PaymentGateway\Adapters;

use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayAdapter
{
    protected Api $razorpay;
    protected PaymentSetting $settings;

    public function __construct(PaymentSetting $settings)
    {
        $this->settings = $settings;
        $this->razorpay = new Api($settings->razorpay_key_id, $settings->razorpay_key_secret);
    }

    /**
     * Create Razorpay subscription.
     */
    public function createSubscription(User $user, SubscriptionPlan $plan, bool $hasTrial, int $trialDays): array
    {
        try {
            // Get or create Razorpay customer
            $customer = $this->getOrCreateCustomer($user);

            if ($hasTrial && $trialDays > 0) {
                // Create subscription with trial period
                return $this->createTrialSubscription($user, $plan, $customer, $trialDays);
            } else {
                // Create subscription with immediate charge
                return $this->createPaidSubscription($user, $plan, $customer);
            }
        } catch (\Exception $e) {
            Log::error('Razorpay subscription creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or create Razorpay customer.
     */
    protected function getOrCreateCustomer(User $user): array
    {
        // Check if user already has a Razorpay customer ID stored
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->where('gateway', 'razorpay')
            ->whereNotNull('gateway_customer_id')
            ->first();

        if ($existingSubscription && $existingSubscription->gateway_customer_id) {
            try {
                $customer = $this->razorpay->customer->fetch($existingSubscription->gateway_customer_id);
                return $customer->toArray();
            } catch (\Exception $e) {
                // Customer doesn't exist in Razorpay, create new one
            }
        }

        // Create new customer
        $customer = $this->razorpay->customer->create([
            'name' => $user->name,
            'email' => $user->email,
            'contact' => $user->phone ?? '',
            'notes' => [
                'user_id' => $user->id,
            ],
        ]);

        return $customer->toArray();
    }

    /**
     * Create subscription with trial period.
     */
    protected function createTrialSubscription(User $user, SubscriptionPlan $plan, array $customer, int $trialDays): array
    {
        // Create Razorpay Plan if not exists
        $planId = $plan->razorpay_plan_id;
        
        if (!$planId) {
            $razorpayPlan = $this->razorpay->plan->create([
                'period' => $this->getRazorpayPeriod($plan->duration_type),
                'interval' => $plan->duration,
                'item' => [
                    'name' => $plan->plan_name,
                    'amount' => (int)($plan->price * 100), // Convert to paise
                    'currency' => 'INR',
                    'description' => $plan->description ?? '',
                ],
            ]);
            $planId = $razorpayPlan->id;
        }

        // Calculate trial end date
        $trialEnd = now()->addDays($trialDays);

        // Create subscription with trial
        $subscription = $this->razorpay->subscription->create([
            'plan_id' => $planId,
            'customer_notify' => 1,
            'total_count' => 999, // Auto-renew indefinitely
            'start_at' => $trialEnd->timestamp,
            'expire_by' => $trialEnd->addDays(1)->timestamp,
            'notes' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'trial_days' => $trialDays,
            ],
        ]);

        // Create payment link for mandate setup
        $paymentLink = $this->razorpay->paymentLink->create([
            'amount' => 0,
            'currency' => 'INR',
            'description' => "Setup payment method for {$plan->plan_name}",
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->phone ?? '',
            ],
            'notify' => [
                'sms' => false,
                'email' => true,
            ],
            'reminder_enable' => false,
            'notes' => [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        return [
            'subscription_id' => $subscription->id,
            'customer_id' => $customer['id'],
            'payment_link_id' => $paymentLink->id,
            'payment_link_url' => $paymentLink->short_url,
            'status' => 'trialing',
            'trial_end' => $trialEnd->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create subscription with immediate charge.
     */
    protected function createPaidSubscription(User $user, SubscriptionPlan $plan, array $customer): array
    {
        // Create Razorpay Plan if not exists
        $planId = $plan->razorpay_plan_id;
        
        if (!$planId) {
            $razorpayPlan = $this->razorpay->plan->create([
                'period' => $this->getRazorpayPeriod($plan->duration_type),
                'interval' => $plan->duration,
                'item' => [
                    'name' => $plan->plan_name,
                    'amount' => (int)($plan->price * 100), // Convert to paise
                    'currency' => 'INR',
                    'description' => $plan->description ?? '',
                ],
            ]);
            $planId = $razorpayPlan->id;
        }

        // Create subscription with immediate charge
        $subscription = $this->razorpay->subscription->create([
            'plan_id' => $planId,
            'customer_notify' => 1,
            'total_count' => 999, // Auto-renew indefinitely
            'start_at' => now()->timestamp,
            'notes' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        // Create order for first payment
        $order = $this->razorpay->order->create([
            'amount' => (int)($plan->price * 100),
            'currency' => 'INR',
            'receipt' => 'sub_' . $subscription->id,
            'notes' => [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        return [
            'subscription_id' => $subscription->id,
            'customer_id' => $customer['id'],
            'order_id' => $order->id,
            'amount' => $plan->price,
            'currency' => 'INR',
            'key_id' => $this->settings->razorpay_key_id,
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

            $this->razorpay->subscription->cancel($subscription->gateway_subscription_id, [
                'cancel_at_cycle_end' => 1, // Cancel at period end
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Razorpay subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle Razorpay webhook.
     */
    public function handleWebhook(array $payload): bool
    {
        $event = $payload['event'] ?? null;
        $data = $payload['payload']['subscription']['entity'] ?? $payload['payload'] ?? null;

        if (!$event || !$data) {
            return false;
        }

        try {
            // Verify webhook signature
            $webhookSecret = config('services.razorpay.webhook_secret');
            if ($webhookSecret) {
                $razorpaySignature = $payload['signature'] ?? null;
                if ($razorpaySignature) {
                    $this->razorpay->utility->verifyWebhookSignature(
                        json_encode($payload),
                        $razorpaySignature,
                        $webhookSecret
                    );
                }
            }

            switch ($event) {
                case 'subscription.activated':
                    $this->handleSubscriptionActivated($data);
                    break;
                
                case 'subscription.charged':
                    $this->handleSubscriptionCharged($data);
                    break;
                
                case 'subscription.halted':
                    $this->handleSubscriptionHalted($data);
                    break;
                
                case 'subscription.cancelled':
                    $this->handleSubscriptionCancelled($data);
                    break;
                
                case 'subscription.paused':
                    $this->handleSubscriptionPaused($data);
                    break;
            }

            return true;
        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Razorpay webhook handling failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle subscription activated.
     */
    protected function handleSubscriptionActivated(array $data): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $data['id'])->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'started_at' => isset($data['start_at']) ? date('Y-m-d H:i:s', $data['start_at']) : now(),
                'next_billing_at' => isset($data['current_end']) ? date('Y-m-d H:i:s', $data['current_end']) : null,
            ]);
        }
    }

    /**
     * Handle subscription charged.
     */
    protected function handleSubscriptionCharged(array $data): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $data['id'])->first();
        
        if ($subscription) {
            $oldStatus = $subscription->status;
            
            if ($subscription->status === 'trialing') {
                $subscription->update([
                    'status' => 'active',
                ]);
            }

            // Update next billing date
            if (isset($data['current_end'])) {
                $subscription->update([
                    'next_billing_at' => date('Y-m-d H:i:s', $data['current_end']),
                ]);
            }

            // Create payment record for the charge
            $this->createPaymentFromRazorpayCharge($subscription, $data);
        }
    }

    /**
     * Handle subscription halted.
     */
    protected function handleSubscriptionHalted(array $data): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $data['id'])->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'past_due',
            ]);
        }
    }

    /**
     * Handle subscription cancelled.
     */
    protected function handleSubscriptionCancelled(array $data): void
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
     * Handle subscription paused.
     */
    protected function handleSubscriptionPaused(array $data): void
    {
        $subscription = Subscription::where('gateway_subscription_id', $data['id'])->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
            ]);
        }
    }

    /**
     * Create payment record from Razorpay charge.
     */
    protected function createPaymentFromRazorpayCharge(Subscription $subscription, array $data): void
    {
        try {
            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                return;
            }

            // Get payment ID from invoice or payment link
            $paymentId = $data['invoice']['payment_id'] ?? $data['payment_id'] ?? null;
            $invoiceId = $data['invoice']['id'] ?? $data['invoice_id'] ?? null;
            $transactionId = $paymentId ?? $invoiceId ?? $data['id'];

            // Check if payment already exists
            $existingPayment = Payment::where('subscription_id', $subscription->id)
                ->where('transaction_id', $transactionId)
                ->first();

            if ($existingPayment) {
                return;
            }

            // Get amount from invoice or subscription
            $amount = 0;
            if (isset($data['invoice']['amount'])) {
                $amount = $data['invoice']['amount'] / 100; // Convert from paise
            } elseif (isset($data['amount'])) {
                $amount = $data['amount'] / 100;
            } else {
                $amount = $plan->price;
            }

            // Try to fetch payment details if payment ID exists
            $paymentMethod = 'other';
            if ($paymentId) {
                try {
                    $payment = $this->razorpay->payment->fetch($paymentId);
                    $paymentMethod = $this->mapRazorpayPaymentMethodToEnum($payment->method ?? 'other');
                } catch (\Exception $e) {
                    Log::warning('Could not fetch Razorpay payment details', [
                        'payment_id' => $paymentId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Payment::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'status' => 'completed',
                'payment_details' => [
                    'subscription_id' => $data['id'] ?? null,
                    'invoice_id' => $invoiceId,
                    'payment_id' => $paymentId,
                    'currency' => $data['invoice']['currency'] ?? $data['currency'] ?? 'INR',
                    'gateway' => 'razorpay',
                ],
                'discount_amount' => 0,
                'final_amount' => $amount,
                'paid_at' => now(),
            ]);

            Log::info('Payment record created from Razorpay charge webhook', [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from Razorpay charge', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map Razorpay payment method to our payment method enum.
     */
    protected function mapRazorpayPaymentMethodToEnum(?string $method): string
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
     * Get Razorpay period from duration type.
     */
    protected function getRazorpayPeriod(string $durationType): string
    {
        return match($durationType) {
            'daily' => 'daily',
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            default => 'monthly',
        };
    }
}

