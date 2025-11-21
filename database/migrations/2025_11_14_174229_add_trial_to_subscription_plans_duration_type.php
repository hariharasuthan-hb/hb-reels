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
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        // SQLite doesn't support MODIFY COLUMN, skip for SQLite (tests)
        // The create migration already has the correct enum values
        if (config('database.default') === 'sqlite' || DB::getDriverName() === 'sqlite') {
            return;
        }

        // Modify the ENUM column to include 'trial' (MySQL/MariaDB only)
        DB::statement("ALTER TABLE `subscription_plans` MODIFY COLUMN `duration_type` ENUM('trial', 'daily', 'weekly', 'monthly', 'yearly') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        DB::statement("ALTER TABLE `subscription_plans` MODIFY COLUMN `duration_type` ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL");
    }
};
