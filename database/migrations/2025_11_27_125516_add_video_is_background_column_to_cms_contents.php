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
            if (!Schema::hasColumn('cms_contents', 'video_is_background')) {
                $table->boolean('video_is_background')->default(false)->after('video_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            if (Schema::hasColumn('cms_contents', 'video_is_background')) {
                $table->dropColumn('video_is_background');
            }
        });
    }
};
