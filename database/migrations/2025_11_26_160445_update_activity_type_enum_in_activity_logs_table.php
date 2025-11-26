<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the column and its index
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['activity_type', 'user_id']);
            $table->dropColumn('activity_type');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('activity_type', ['gym_checkin', 'event_reel_generation'])
                  ->default('gym_checkin')
                  ->after('user_id');
            $table->index(['activity_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['activity_type', 'user_id']);
            $table->dropColumn('activity_type');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('activity_type', ['gym_checkin', 'video_generation'])
                  ->default('gym_checkin')
                  ->after('user_id');
            $table->index(['activity_type', 'user_id']);
        });
    }
};
