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
        // For SQLite, we need to recreate the column with new enum values
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('check_in_method');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('check_in_method', ['qr_code', 'rfid', 'manual', 'web'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('check_in_method');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('check_in_method', ['qr_code', 'rfid', 'manual'])->nullable();
        });
    }
};
