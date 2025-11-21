<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentGateway\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService
    ) {
    }

    /**
     * Handle Stripe webhook.
     */
    public function stripe(Request $request): Response
    {
        $payload = $request->all();
        $sigHeader = $request->header('Stripe-Signature');
        
        try {
            // Verify webhook signature
            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $webhookSecret = config('services.stripe.webhook_secret');
            
            if ($webhookSecret && $sigHeader) {
                \Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $sigHeader,
                    $webhookSecret
                );
            }

            // Handle webhook
            $this->paymentGatewayService->handleWebhook('stripe', $payload);

            // Also handle subscription creation for checkout sessions
            if (isset($payload['type']) && $payload['type'] === 'checkout.session.completed') {
                $this->handleStripeCheckoutCompleted($payload['data']['object']);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return response('Error', 400);
        }
    }

    /**
     * Handle Razorpay webhook.
     */
    public function razorpay(Request $request): Response
    {
        $payload = $request->all();

        try {
            $this->paymentGatewayService->handleWebhook('razorpay', $payload);
            
            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Razorpay webhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return response('Error', 400);
        }
    }

    /**
     * Handle Stripe checkout session completed.
     */
    protected function handleStripeCheckoutCompleted(array $session): void
    {
        try {
            $userId = $session['metadata']['user_id'] ?? null;
            $planId = $session['metadata']['plan_id'] ?? null;
            $subscriptionId = $session['subscription'] ?? null;

            if (!$userId || !$planId || !$subscriptionId) {
                return;
            }

            $user = User::find($userId);
            $plan = SubscriptionPlan::find($planId);

            if (!$user || !$plan) {
                return;
            }

            // Check if subscription already exists
            $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)
                ->where('gateway', 'stripe')
                ->first();

            if ($subscription) {
                return;
            }

            // Get subscription details from Stripe
            $paymentSettings = \App\Models\PaymentSetting::getSettings();
            $stripe = new \Stripe\StripeClient($paymentSettings->stripe_secret_key);
            $stripeSubscription = $stripe->subscriptions->retrieve($subscriptionId);

            // Create subscription record
            Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'gateway' => 'stripe',
                'gateway_customer_id' => $stripeSubscription->customer,
                'gateway_subscription_id' => $subscriptionId,
                'status' => $this->mapStripeStatus($stripeSubscription->status),
                'trial_end_at' => $stripeSubscription->trial_end ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) : null,
                'next_billing_at' => $stripeSubscription->current_period_end ? date('Y-m-d H:i:s', $stripeSubscription->current_period_end) : null,
                'started_at' => $stripeSubscription->start_date ? date('Y-m-d H:i:s', $stripeSubscription->start_date) : now(),
                'metadata' => [
                    'checkout_session_id' => $session['id'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle Stripe checkout completion', [
                'error' => $e->getMessage(),
                'session' => $session,
            ]);
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
}

