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
        // Check database connection type and handle accordingly
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            // For SQLite, recreate the column
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('check_in_method');
            });

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->enum('check_in_method', ['qr_code', 'rfid', 'manual', 'web'])->nullable();
            });
        } else {
            // For MySQL and other databases, use ALTER TABLE
            DB::statement("ALTER TABLE activity_logs MODIFY COLUMN check_in_method ENUM('qr_code', 'rfid', 'manual', 'web') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check database connection type and handle accordingly
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            // For SQLite, recreate the column
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('check_in_method');
            });

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->enum('check_in_method', ['qr_code', 'rfid', 'manual'])->nullable();
            });
        } else {
            // For MySQL and other databases, use ALTER TABLE
            DB::statement("ALTER TABLE activity_logs MODIFY COLUMN check_in_method ENUM('qr_code', 'rfid', 'manual') NULL");
        }
    }
};
