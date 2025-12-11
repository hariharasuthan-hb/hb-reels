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
            $table->string('job_id')->nullable()->after('video_size_bytes');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->nullable()->after('job_id');
            $table->text('error_message')->nullable()->after('status');

            $table->index(['status', 'user_id']);
            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'user_id']);
            $table->dropIndex(['job_id']);

            $table->dropColumn(['job_id', 'status', 'error_message']);
        });
    }
};
