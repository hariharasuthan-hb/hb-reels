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
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'tenant_id')) {
            Schema::table('payments', function (Blueprint $table) {
                // Make tenant_id nullable
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'tenant_id')) {
            Schema::table('payments', function (Blueprint $table) {
                // Revert to not nullable (but this might fail if there are null values)
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            });
        }
    }
};
