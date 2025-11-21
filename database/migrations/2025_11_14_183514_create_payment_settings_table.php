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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enable_stripe')->default(false);
            $table->text('stripe_publishable_key')->nullable();
            $table->text('stripe_secret_key')->nullable();
            $table->boolean('enable_razorpay')->default(false);
            $table->text('razorpay_key_id')->nullable();
            $table->text('razorpay_key_secret')->nullable();
            $table->boolean('enable_gpay')->default(false);
            $table->text('gpay_upi_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
