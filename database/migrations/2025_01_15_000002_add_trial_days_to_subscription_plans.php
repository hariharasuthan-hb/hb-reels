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
        if (!Schema::hasTable('subscription_plans')) {
            // If table doesn't exist, this migration will be handled by create_subscription_plans_table
            return;
        }

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'trial_days')) {
                $table->integer('trial_days')->default(0)->after('price');
            }
            if (!Schema::hasColumn('subscription_plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('trial_days');
            }
            if (!Schema::hasColumn('subscription_plans', 'razorpay_plan_id')) {
                $table->string('razorpay_plan_id')->nullable()->after('stripe_price_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['trial_days', 'stripe_price_id', 'razorpay_plan_id']);
        });
    }
};

