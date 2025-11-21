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
        Schema::create('workout_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('exercise_name'); // Name of the exercise
            $table->string('video_path'); // Path to the video file
            $table->integer('duration_seconds')->default(60); // Video duration (should be 60 seconds)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('review_feedback')->nullable(); // Feedback from reviewer
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null'); // Reviewer who approved/rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['workout_plan_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_videos');
    }
};
