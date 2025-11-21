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
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('export_type'); // payments, invoices, expenses, incomes, subscriptions
            $table->string('filename')->nullable();
            $table->string('filepath')->nullable();
            $table->string('format')->default('csv'); // csv, xlsx
            $table->json('filters')->nullable(); // Store filter parameters
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['export_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
