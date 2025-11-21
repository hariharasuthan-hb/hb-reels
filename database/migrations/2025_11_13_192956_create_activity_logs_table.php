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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->text('workout_summary')->nullable();
            $table->integer('duration_minutes')->nullable(); // Workout duration in minutes
            $table->decimal('calories_burned', 8, 2)->nullable();
            $table->text('exercises_done')->nullable(); // JSON or text field
            $table->text('performance_metrics')->nullable(); // JSON field for additional metrics
            $table->enum('check_in_method', ['qr_code', 'rfid', 'manual'])->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->onDelete('set null'); // For manual check-ins by trainers/admins
            $table->timestamps();
            
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
