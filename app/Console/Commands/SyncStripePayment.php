<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SyncStripePayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-payment {payment_intent_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync a payment record from Stripe payment intent ID';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $paymentIntentId = $this->argument('payment_intent_id');
        
        if (!$paymentIntentId) {
            $this->error('Payment intent ID is required');
            return Command::FAILURE;
        }

        $this->info("Syncing payment intent: {$paymentIntentId}");

        try {
            $paymentSettings = PaymentSetting::getSettings();
            $stripe = new StripeClient($paymentSettings->stripe_secret_key);
            
            // Retrieve payment intent from Stripe
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
            
            if ($paymentIntent->status !== 'succeeded') {
                $this->warn("Payment intent status is '{$paymentIntent->status}', not 'succeeded'");
                return Command::FAILURE;
            }

            // Check if payment already exists
            $existingPayment = null;
            if (Payment::hasTransactionIdColumn()) {
                $existingPayment = Payment::where('transaction_id', $paymentIntentId)->first();
            } else {
                // If transaction_id doesn't exist, check by subscription_id and amount
                // This is a fallback for accounting system tables
                if (isset($paymentIntent->invoice)) {
                    $invoice = $stripe->invoices->retrieve($paymentIntent->invoice);
                    if (isset($invoice->subscription)) {
                        $subscription = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
                        if ($subscription) {
                            $amount = $paymentIntent->amount / 100;
                            $existingPayment = Payment::where('subscription_id', $subscription->id)
                                ->where('amount', $amount)
                                ->first();
                        }
                    }
                }
            }
            
            if ($existingPayment) {
                $this->info("Payment record already exists (ID: {$existingPayment->id})");
                return Command::SUCCESS;
            }

            // Find subscription by invoice
            $subscription = null;
            if (isset($paymentIntent->invoice)) {
                $invoice = $stripe->invoices->retrieve($paymentIntent->invoice);
                if (isset($invoice->subscription)) {
                    $subscription = Subscription::where('gateway_subscription_id', $invoice->subscription)->first();
                }
            }

            // If not found, try to find by customer and metadata
            if (!$subscription && isset($paymentIntent->customer)) {
                $subscription = Subscription::where('gateway_customer_id', $paymentIntent->customer)
                    ->whereIn('status', ['active', 'trialing', 'pending'])
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if (!$subscription) {
                $this->error('Could not find subscription for this payment intent');
                $this->info('Payment Intent Details:');
                $this->info('  Customer: ' . ($paymentIntent->customer ?? 'N/A'));
                $this->info('  Amount: $' . number_format($paymentIntent->amount / 100, 2));
                $this->info('  Status: ' . $paymentIntent->status);
                return Command::FAILURE;
            }

            $plan = $subscription->subscriptionPlan;
            if (!$plan) {
                $this->error('Subscription plan not found');
                return Command::FAILURE;
            }

            $amount = $paymentIntent->amount / 100; // Convert from cents
            $paymentMethod = $this->mapPaymentMethod($paymentIntent->payment_method_types ?? ['card']);

            // Build payment data array
            $paymentData = [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'payment_details' => [
                    'payment_intent_id' => $paymentIntentId,
                    'currency' => $paymentIntent->currency ?? 'usd',
                    'gateway' => 'stripe',
                    'synced_at' => now()->toIso8601String(),
                ],
                'discount_amount' => 0,
                'paid_at' => date('Y-m-d H:i:s', $paymentIntent->created),
            ];

            // Add accounting system required columns if they exist
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'date')) {
                $paymentData['date'] = date('Y-m-d', $paymentIntent->created);
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'is_credit')) {
                $paymentData['is_credit'] = 0; // Payment is a debit (money coming in)
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'currency_code')) {
                $paymentData['currency_code'] = strtoupper($paymentIntent->currency ?? 'USD');
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
                $paymentData['status'] = 'completed';
            }
            
            if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'final_amount')) {
                $paymentData['final_amount'] = $amount;
            }

            $payment = Payment::create($paymentData);

            $this->info("✅ Payment record created successfully!");
            $this->info("   Payment ID: {$payment->id}");
            $this->info("   Subscription ID: {$subscription->id}");
            $this->info("   Amount: $" . number_format($amount, 2));
            $this->info("   User: {$subscription->user->email}");

            // Update subscription status if it's pending
            if ($subscription->status === 'pending') {
                $status = ($plan->hasTrial()) ? 'trialing' : 'active';
                $subscription->update([
                    'status' => $status,
                    'started_at' => now(),
                ]);
                $this->info("   Subscription status updated to: {$status}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to sync payment: " . $e->getMessage());
            Log::error('Failed to sync Stripe payment', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Map Stripe payment method to our payment method enum.
     */
    protected function mapPaymentMethod(array $paymentMethodTypes): string
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
}
