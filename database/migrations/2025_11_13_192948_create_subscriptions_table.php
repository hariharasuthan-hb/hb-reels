<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            
            // Payment gateway fields (for new installations)
            $table->string('gateway')->nullable(); // 'stripe' or 'razorpay'
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_subscription_id')->nullable();
            
            // Updated status enum
            $table->enum('status', ['trialing', 'active', 'canceled', 'past_due', 'expired', 'pending'])->default('pending');
            
            // Subscription lifecycle dates
            $table->timestamp('trial_end_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            
            // Metadata for storing additional gateway information
            $table->json('metadata')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
