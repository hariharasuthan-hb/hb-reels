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
        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->text('meal_plan')->nullable(); // JSON field for meal plans
            $table->text('nutritional_goals')->nullable();
            $table->integer('target_calories')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'paused', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diet_plans');
    }
};
