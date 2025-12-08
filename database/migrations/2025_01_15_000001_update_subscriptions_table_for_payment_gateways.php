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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('subscription_plan_id');
            $table->string('gateway_customer_id')->nullable()->after('gateway');
            $table->string('gateway_subscription_id')->nullable()->after('gateway_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_customer_id', 'gateway_subscription_id']);
        });
    }
};
