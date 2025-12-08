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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->integer('age')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('age');
            $table->text('address')->nullable()->after('gender');
            $table->string('qr_code')->nullable()->unique()->after('address');
            $table->string('rfid_card')->nullable()->unique()->after('qr_code');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('rfid_card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'age', 'gender', 'address', 'qr_code', 'rfid_card', 'status']);
        });
    }
};
