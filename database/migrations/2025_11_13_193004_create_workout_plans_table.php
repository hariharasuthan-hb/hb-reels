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
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
            $table->string('plan_name');
            $table->text('description')->nullable();
            $table->text('exercises')->nullable(); // JSON field for exercises
            $table->integer('duration_weeks')->nullable();
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
        Schema::dropIfExists('workout_plans');
    }
};
