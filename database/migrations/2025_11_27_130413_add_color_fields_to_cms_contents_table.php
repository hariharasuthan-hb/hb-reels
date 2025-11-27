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
            if (!Schema::hasColumn('cms_contents', 'title_color')) {
                $table->string('title_color', 7)->nullable()->after('description');
            }
            if (!Schema::hasColumn('cms_contents', 'description_color')) {
                $table->string('description_color', 7)->nullable()->after('title_color');
            }
            if (!Schema::hasColumn('cms_contents', 'content_color')) {
                $table->string('content_color', 7)->nullable()->after('description_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            if (Schema::hasColumn('cms_contents', 'title_color')) {
                $table->dropColumn('title_color');
            }
            if (Schema::hasColumn('cms_contents', 'description_color')) {
                $table->dropColumn('description_color');
            }
            if (Schema::hasColumn('cms_contents', 'content_color')) {
                $table->dropColumn('content_color');
            }
        });
    }
};
