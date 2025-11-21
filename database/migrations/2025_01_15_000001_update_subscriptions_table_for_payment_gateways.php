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
        if (!Schema::hasTable('subscriptions')) {
            // If table doesn't exist, this migration will be handled by create_subscriptions_table
            return;
        }

        // Check if old columns exist before dropping
        if (Schema::hasColumn('subscriptions', 'start_date')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn(['start_date', 'end_date', 'auto_renew']);
            });
        }
        
        // Drop old status column if it exists and has old enum values
        if (Schema::hasColumn('subscriptions', 'status')) {
            // Check if we need to modify status column
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn(['status']);
            });
        }
        
        // Add new columns if they don't exist
        Schema::table('subscriptions', function (Blueprint $table) {
            // Payment gateway fields
            if (!Schema::hasColumn('subscriptions', 'gateway')) {
                $table->string('gateway')->nullable()->after('subscription_plan_id');
            }
            if (!Schema::hasColumn('subscriptions', 'gateway_customer_id')) {
                $table->string('gateway_customer_id')->nullable()->after('gateway');
            }
            if (!Schema::hasColumn('subscriptions', 'gateway_subscription_id')) {
                $table->string('gateway_subscription_id')->nullable()->after('gateway_customer_id');
            }
            
            // Updated status enum
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->enum('status', ['trialing', 'active', 'canceled', 'past_due', 'expired', 'pending'])->default('pending')->after('gateway_subscription_id');
            }
            
            // Subscription lifecycle dates
            if (!Schema::hasColumn('subscriptions', 'trial_end_at')) {
                $table->timestamp('trial_end_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('subscriptions', 'next_billing_at')) {
                $table->timestamp('next_billing_at')->nullable()->after('trial_end_at');
            }
            if (!Schema::hasColumn('subscriptions', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('next_billing_at');
            }
            if (!Schema::hasColumn('subscriptions', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('started_at');
            }
            
            // Metadata for storing additional gateway information
            if (!Schema::hasColumn('subscriptions', 'metadata')) {
                $table->json('metadata')->nullable()->after('canceled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_customer_id',
                'gateway_subscription_id',
                'status',
                'trial_end_at',
                'next_billing_at',
                'started_at',
                'canceled_at',
                'metadata',
            ]);
        });
        
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('start_date')->after('subscription_plan_id');
            $table->date('end_date')->after('start_date');
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending')->after('end_date');
            $table->boolean('auto_renew')->default(false)->after('status');
        });
    }
};

