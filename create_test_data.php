<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;

// Create subscription plans
echo "Creating subscription plans...\n";
$basicPlan = SubscriptionPlan::create([
    'plan_name' => 'Basic Plan',
    'description' => 'Basic subscription plan for video generation',
    'duration_type' => 'monthly',
    'duration' => 1,
    'price' => 29.99,
    'trial_days' => 7,
    'is_active' => true,
    'features' => 'Video generation, Basic support'
]);

$premiumPlan = SubscriptionPlan::create([
    'plan_name' => 'Premium Plan',
    'description' => 'Premium subscription plan with unlimited access',
    'duration_type' => 'monthly',
    'duration' => 1,
    'price' => 49.99,
    'trial_days' => 14,
    'is_active' => true,
    'features' => 'Unlimited video generation, Priority support, Advanced features'
]);

// Create test users
echo "Creating test users...\n";
$memberWithSubscription = User::create([
    'name' => 'John Doe (Subscribed)',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);

$memberWithoutSubscription = User::create([
    'name' => 'Jane Smith (No Subscription)',
    'email' => 'jane@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);

$adminUser = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);

// Assign roles
$memberRole = Spatie\Permission\Models\Role::where('name', 'member')->first();
$adminRole = Spatie\Permission\Models\Role::where('name', 'admin')->first();

if ($memberRole) {
    $memberWithSubscription->assignRole($memberRole);
    $memberWithoutSubscription->assignRole($memberRole);
}

if ($adminRole) {
    $adminUser->assignRole($adminRole);
}

// Create subscriptions
echo "Creating subscriptions...\n";
Subscription::create([
    'user_id' => $memberWithSubscription->id,
    'subscription_plan_id' => $basicPlan->id,
    'status' => 'active',
    'next_billing_at' => now()->addMonth(),
    'started_at' => now(),
]);

echo "Test data created successfully!\n";
echo "Users created:\n";
echo "- Member with subscription: john@example.com (password: password)\n";
echo "- Member without subscription: jane@example.com (password: password)\n";
echo "- Admin user: admin@example.com (password: password)\n";
echo "\nSubscription Plans:\n";
echo "- Basic Plan: $29.99/month, 7 day trial\n";
echo "- Premium Plan: $49.99/month, 14 day trial\n";
