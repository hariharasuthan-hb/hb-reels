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
        Schema::table('cms_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('cms_contents', 'background_video')) {
                $table->string('background_video')->nullable()->after('video_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            if (Schema::hasColumn('cms_contents', 'background_video')) {
                $table->dropColumn('background_video');
            }
        });
    }
};
