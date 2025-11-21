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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();

            // Basic plan info
            $table->string('plan_name');
            $table->text('description')->nullable();

            // Duration settings
            $table->enum('duration_type', ['trial', 'daily', 'weekly', 'monthly', 'yearly']);
            $table->integer('duration');  // Number of units based on duration_type

            // Pricing
            $table->decimal('price', 10, 2);
            $table->integer('trial_days')->default(0);

            // Gateway identifiers
            $table->string('stripe_price_id')->nullable();
            $table->string('razorpay_plan_id')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Plan features (JSON or text)
            $table->text('features')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
