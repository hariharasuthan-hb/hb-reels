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
        // Drop the existing unique constraint on the key column
        // This allows soft deleted records to have the same key
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropUnique(['key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the unique constraint on the key column
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->unique('key');
        });
    }
};
