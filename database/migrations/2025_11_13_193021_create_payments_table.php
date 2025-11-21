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
        // Check if payments table already exists
        if (Schema::hasTable('payments')) {
            // Add missing columns to existing table
            Schema::table('payments', function (Blueprint $table) {
                // Add user_id if it doesn't exist
                if (!Schema::hasColumn('payments', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                }
                
                // Add subscription_id if it doesn't exist
                if (!Schema::hasColumn('payments', 'subscription_id')) {
                    $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
                }
                
                // Add payment_method if it doesn't exist
                if (!Schema::hasColumn('payments', 'payment_method')) {
                    $table->enum('payment_method', ['credit_card', 'debit_card', 'upi', 'paypal', 'cash', 'bank_transfer', 'other'])->nullable();
                }
                
                // Add status if it doesn't exist
                if (!Schema::hasColumn('payments', 'status')) {
                    $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
                }
                
                // Add payment_details if it doesn't exist
                if (!Schema::hasColumn('payments', 'payment_details')) {
                    $table->text('payment_details')->nullable();
                }
                
                // Add promotional_code if it doesn't exist
                if (!Schema::hasColumn('payments', 'promotional_code')) {
                    $table->string('promotional_code')->nullable();
                }
                
                // Add discount_amount if it doesn't exist
                if (!Schema::hasColumn('payments', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0);
                }
                
                // Add final_amount if it doesn't exist
                if (!Schema::hasColumn('payments', 'final_amount')) {
                    $table->decimal('final_amount', 10, 2)->nullable();
                }
                
                // Add paid_at if it doesn't exist
                if (!Schema::hasColumn('payments', 'paid_at')) {
                    $table->dateTime('paid_at')->nullable();
                }
            });
            
            return;
        }
        
        // Create new table if it doesn't exist
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'upi', 'paypal', 'cash', 'bank_transfer', 'other']);
            $table->string('transaction_id')->unique()->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('payment_details')->nullable(); // JSON field for payment gateway response
            $table->string('promotional_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
