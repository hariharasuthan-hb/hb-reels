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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('activity_type', ['gym_checkin', 'video_generation'])
                  ->default('gym_checkin')
                  ->after('user_id');
            $table->string('video_filename')->nullable()->after('performance_metrics');
            $table->string('video_caption')->nullable()->after('video_filename');
            $table->string('video_path')->nullable()->after('video_caption');
            $table->integer('video_size_bytes')->nullable()->after('video_path');

            $table->index(['activity_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['activity_type', 'user_id']);
            $table->dropColumn(['activity_type', 'video_filename', 'video_caption', 'video_path', 'video_size_bytes']);
        });
    }
};
