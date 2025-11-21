<?php

namespace App\Services\PaymentGateway;

use App\Models\PaymentSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentGateway\Adapters\StripeAdapter;
use App\Services\PaymentGateway\Adapters\RazorpayAdapter;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected ?StripeAdapter $stripeAdapter = null;
    protected ?RazorpayAdapter $razorpayAdapter = null;
    protected PaymentSetting $paymentSettings;

    public function __construct()
    {
        $this->paymentSettings = PaymentSetting::getSettings();
        
        if ($this->paymentSettings->enable_stripe) {
            $this->stripeAdapter = new StripeAdapter($this->paymentSettings);
        }
        
        if ($this->paymentSettings->enable_razorpay) {
            $this->razorpayAdapter = new RazorpayAdapter($this->paymentSettings);
        }
    }

    /**
     * Get available payment gateways.
     */
    public function getAvailableGateways(): array
    {
        $gateways = [];
        
        if ($this->stripeAdapter) {
            $gateways['stripe'] = 'Stripe';
        }
        
        if ($this->razorpayAdapter) {
            $gateways['razorpay'] = 'Razorpay';
        }
        
        return $gateways;
    }

    /**
     * Create subscription for user with plan.
     */
    public function createSubscription(User $user, SubscriptionPlan $plan, string $gateway): array
    {
        if (!in_array($gateway, ['stripe', 'razorpay'])) {
            throw new \InvalidArgumentException("Invalid payment gateway: {$gateway}");
        }

        $hasTrial = $plan->hasTrial();
        $trialDays = $plan->getTrialDays();

        if ($gateway === 'stripe') {
            if (!$this->stripeAdapter) {
                throw new \Exception('Stripe is not enabled');
            }
            
            return $this->stripeAdapter->createSubscription($user, $plan, $hasTrial, $trialDays);
        }

        if ($gateway === 'razorpay') {
            if (!$this->razorpayAdapter) {
                throw new \Exception('Razorpay is not enabled');
            }
            
            return $this->razorpayAdapter->createSubscription($user, $plan, $hasTrial, $trialDays);
        }

        throw new \Exception('Payment gateway not supported');
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(\App\Models\Subscription $subscription): bool
    {
        if ($subscription->gateway === 'stripe') {
            return $this->stripeAdapter?->cancelSubscription($subscription) ?? false;
        }

        if ($subscription->gateway === 'razorpay') {
            return $this->razorpayAdapter?->cancelSubscription($subscription) ?? false;
        }

        return false;
    }

    /**
     * Handle webhook from payment gateway.
     */
    public function handleWebhook(string $gateway, array $payload): bool
    {
        try {
            if ($gateway === 'stripe') {
                return $this->stripeAdapter?->handleWebhook($payload) ?? false;
            }

            if ($gateway === 'razorpay') {
                return $this->razorpayAdapter?->handleWebhook($payload) ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Webhook handling failed for {$gateway}", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }
}

