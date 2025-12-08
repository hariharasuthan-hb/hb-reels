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
            $table->longText('workout_summary')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('calories_burned', 8, 2)->nullable();
            $table->longText('exercises_done')->nullable();
            $table->longText('performance_metrics')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('video_filename')->nullable();
            $table->string('video_caption')->nullable();
            $table->string('video_path')->nullable();
            $table->bigInteger('video_size_bytes')->nullable();
            $table->enum('activity_type', ['gym_checkin', 'event_reel_generation'])->default('gym_checkin');
            $table->enum('check_in_method', ['qr_code', 'rfid', 'manual', 'web'])->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['activity_type', 'user_id']);
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
