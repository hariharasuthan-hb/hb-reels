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
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('audience_type', ['all', 'trainer', 'member', 'user'])->default('all');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('requires_acknowledgement')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audience_type', 'status']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
    }
};


