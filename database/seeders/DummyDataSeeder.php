<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    /**
     * Configuration for dummy data generation
     */
    private array $config = [];

    /**
     * Set configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Configuration - adjust these numbers for load testing
        $config = $this->config ?: [
            'users' => 1000,           // Number of users to create
            'subscription_plans' => 5, // Number of subscription plans (if none exist)
            'subscriptions' => 2000,   // Number of subscriptions
            'payments' => 5000,        // Number of payments
            'incomes' => 500,          // Number of income records
            'expenses' => 500,         // Number of expense records
        ];

        $this->command->info('Starting dummy data generation for load testing...');

        // Create users if needed
        $existingUsers = User::count();
        if ($existingUsers < $config['users']) {
            $usersToCreate = $config['users'] - $existingUsers;
            $this->command->info("Creating {$usersToCreate} users...");
            $this->createUsers($faker, $usersToCreate);
        } else {
            $this->command->info("Using existing {$existingUsers} users");
        }

        // Get or create subscription plans
        $subscriptionPlans = $this->getOrCreateSubscriptionPlans($faker, $config['subscription_plans']);
        $this->command->info("Using " . count($subscriptionPlans) . " subscription plans");

        // Get all users for relationships
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->error('No users found. Please create users first.');
            return;
        }

        // Create subscriptions
        $this->command->info("Creating {$config['subscriptions']} subscriptions...");
        $subscriptions = $this->createSubscriptions($faker, $users, $subscriptionPlans, $config['subscriptions']);

        // Create payments
        $this->command->info("Creating {$config['payments']} payments...");
        $this->createPayments($faker, $users, $subscriptions, $config['payments']);

        // Create incomes
        $this->command->info("Creating {$config['incomes']} income records...");
        $this->createIncomes($faker, $config['incomes']);

        // Create expenses
        $this->command->info("Creating {$config['expenses']} expense records...");
        $this->createExpenses($faker, $config['expenses']);

        $this->command->info('Dummy data generation completed!');
    }

    /**
     * Create dummy users
     */
    private function createUsers($faker, int $count): void
    {
        $users = [];
        $batchSize = 500;
        
        // Pre-hash password once for all users (faster for dummy data)
        $hashedPassword = Hash::make('password');

        for ($i = 0; $i < $count; $i++) {
            $users[] = [
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => $hashedPassword,
                'phone' => $faker->phoneNumber(),
                'age' => $faker->numberBetween(18, 70),
                'gender' => $faker->randomElement(['male', 'female', 'other']),
                'address' => $faker->address(),
                'email_verified_at' => $faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
                'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                'updated_at' => now(),
            ];

            // Insert in batches for better performance
            if (($i + 1) % $batchSize === 0 || ($i + 1) === $count) {
                User::insert($users);
                $users = [];
                $this->command->info("  Created " . min($i + 1, $count) . " / {$count} users...");
            }
        }
    }

    /**
     * Get existing subscription plans or create new ones
     */
    private function getOrCreateSubscriptionPlans($faker, int $count): array
    {
        $existingPlans = SubscriptionPlan::all();
        
        if ($existingPlans->isEmpty()) {
            $plans = [];
            $durationTypes = ['monthly', 'yearly', 'weekly', 'daily'];
            $prices = [29.99, 49.99, 79.99, 99.99, 149.99, 199.99];

            for ($i = 0; $i < $count; $i++) {
                $durationType = $faker->randomElement($durationTypes);
                $duration = $durationType === 'monthly' ? 1 : ($durationType === 'yearly' ? 12 : ($durationType === 'weekly' ? 1 : 1));
                
                $plans[] = SubscriptionPlan::create([
                    'plan_name' => $faker->randomElement(['Basic', 'Standard', 'Premium', 'Pro', 'Elite']) . ' Plan',
                    'description' => $faker->sentence(10),
                    'duration_type' => $durationType,
                    'duration' => $duration,
                    'price' => $faker->randomElement($prices),
                    'trial_days' => $faker->optional(0.5)->numberBetween(7, 14),
                    'is_active' => true,
                    'features' => [
                        $faker->sentence(3),
                        $faker->sentence(3),
                        $faker->sentence(3),
                    ],
                    'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => now(),
                ]);
            }
            
            return $plans;
        }

        return $existingPlans->toArray();
    }

    /**
     * Create dummy subscriptions
     */
    private function createSubscriptions($faker, $users, array $subscriptionPlans, int $count): array
    {
        $subscriptions = [];
        $batchSize = 100;
        $statuses = Subscription::getStatusOptions();
        $gateways = Subscription::getGatewayOptions();

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $plan = $faker->randomElement($subscriptionPlans);
            $status = $faker->randomElement($statuses);
            $startedAt = $faker->dateTimeBetween('-2 years', 'now');
            
            // Calculate dates based on status
            $trialEndAt = null;
            $nextBillingAt = null;
            $canceledAt = null;

            if ($status === 'trialing') {
                $trialEndAt = $faker->dateTimeBetween($startedAt, '+14 days');
            }

            if (in_array($status, ['active', 'trialing'])) {
                $nextBillingAt = $faker->dateTimeBetween('now', '+30 days');
            }

            if ($status === 'canceled') {
                $canceledAt = $faker->dateTimeBetween($startedAt, 'now');
            }

            $subscriptions[] = [
                'user_id' => $user->id,
                'subscription_plan_id' => is_array($plan) ? $plan['id'] : $plan->id,
                'gateway' => $faker->randomElement($gateways),
                'gateway_customer_id' => 'cus_' . $faker->bothify('##########'),
                'gateway_subscription_id' => 'sub_' . $faker->bothify('##########'),
                'status' => $status,
                'trial_end_at' => $trialEndAt,
                'next_billing_at' => $nextBillingAt,
                'started_at' => $startedAt,
                'canceled_at' => $canceledAt,
                'metadata' => null,
                'created_at' => $startedAt,
                'updated_at' => now(),
            ];

            // Insert in batches
            if (($i + 1) % $batchSize === 0 || ($i + 1) === $count) {
                Subscription::insert($subscriptions);
                $subscriptions = [];
                $this->command->info("  Created " . min($i + 1, $count) . " / {$count} subscriptions...");
            }
        }

        return Subscription::latest()->take($count)->get()->toArray();
    }

    /**
     * Create dummy payments
     */
    private function createPayments($faker, $users, array $subscriptions, int $count): void
    {
        $payments = [];
        $batchSize = 100;
        $statuses = Payment::getStatusOptions();
        $allMethods = Payment::getPaymentMethodOptions();
        
        // Filter methods to only valid enum values (check database schema)
        // Valid enum values: credit_card, debit_card, upi, paypal, cash, bank_transfer, other
        $validMethods = ['credit_card', 'debit_card', 'upi', 'paypal', 'cash', 'bank_transfer', 'other'];
        $methods = array_intersect($allMethods, $validMethods);
        if (empty($methods)) {
            $methods = $validMethods; // Fallback to valid methods
        }
        
        // Check which columns exist
        $paymentTable = (new Payment())->getTable();
        $hasTransactionId = \Illuminate\Support\Facades\Schema::hasColumn($paymentTable, 'transaction_id');
        $hasDate = \Illuminate\Support\Facades\Schema::hasColumn($paymentTable, 'date');

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $subscription = $faker->optional(0.7)->randomElement($subscriptions);
            $status = $faker->randomElement($statuses);
            $method = $faker->randomElement($methods);
            $amount = $faker->randomFloat(2, 10, 500);
            $discountAmount = $faker->optional(0.3)->randomFloat(2, 5, 50) ?? 0;
            $finalAmount = $amount - $discountAmount;
            $paidAt = $faker->dateTimeBetween('-2 years', 'now');

            $paymentData = [
                'user_id' => $user->id,
                'subscription_id' => $subscription ? (is_array($subscription) ? $subscription['id'] : $subscription->id) : null,
                'amount' => $amount,
                'payment_method' => $method,
                'status' => $status,
                'payment_details' => json_encode([
                    'card_last4' => $faker->numerify('####'),
                    'card_brand' => $faker->randomElement(['visa', 'mastercard', 'amex']),
                ]),
                'promotional_code' => $faker->optional(0.2)->bothify('PROMO####'),
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'paid_at' => $status === 'completed' ? $paidAt : null,
                'created_at' => $paidAt,
                'updated_at' => now(),
            ];
            
            // Only add transaction_id if column exists
            if ($hasTransactionId) {
                $paymentData['transaction_id'] = 'txn_' . $faker->bothify('##########');
            }
            
            // Add date column if it exists (for accounting system)
            if ($hasDate) {
                $paymentData['date'] = $paidAt->format('Y-m-d');
            }
            
            $payments[] = $paymentData;

            // Insert in batches
            if (($i + 1) % $batchSize === 0 || ($i + 1) === $count) {
                Payment::insert($payments);
                $payments = [];
                $this->command->info("  Created " . min($i + 1, $count) . " / {$count} payments...");
            }
        }
    }

    /**
     * Create dummy income records
     */
    private function createIncomes($faker, int $count): void
    {
        $incomes = [];
        $batchSize = 100;
        $categories = ['Membership Fees', 'Personal Training', 'Equipment Sales', 'Merchandise', 'Other Services'];
        $methods = Income::getPaymentMethodOptions();

        for ($i = 0; $i < $count; $i++) {
            $incomes[] = [
                'category' => $faker->randomElement($categories),
                'source' => $faker->company(),
                'amount' => $faker->randomFloat(2, 50, 2000),
                'received_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'payment_method' => $faker->randomElement($methods),
                'reference' => $faker->optional(0.7)->bothify('REF-########'),
                'notes' => $faker->optional(0.5)->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches
            if (($i + 1) % $batchSize === 0 || ($i + 1) === $count) {
                Income::insert($incomes);
                $incomes = [];
                $this->command->info("  Created " . min($i + 1, $count) . " / {$count} incomes...");
            }
        }
    }

    /**
     * Create dummy expense records
     */
    private function createExpenses($faker, int $count): void
    {
        $expenses = [];
        $batchSize = 100;
        $categories = ['Rent', 'Utilities', 'Equipment', 'Salaries', 'Marketing', 'Maintenance', 'Supplies', 'Other'];
        $vendors = ['ABC Equipment Co.', 'XYZ Supplies', 'Maintenance Pro', 'Marketing Agency', 'Utility Company'];
        $methods = Expense::getPaymentMethodOptions();

        for ($i = 0; $i < $count; $i++) {
            $expenses[] = [
                'category' => $faker->randomElement($categories),
                'vendor' => $faker->randomElement($vendors),
                'amount' => $faker->randomFloat(2, 20, 5000),
                'spent_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'payment_method' => $faker->randomElement($methods),
                'reference' => $faker->optional(0.7)->bothify('INV-########'),
                'notes' => $faker->optional(0.5)->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches
            if (($i + 1) % $batchSize === 0 || ($i + 1) === $count) {
                Expense::insert($expenses);
                $expenses = [];
                $this->command->info("  Created " . min($i + 1, $count) . " / {$count} expenses...");
            }
        }
    }
}

