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
        Schema::create('cms_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('key')->unique();
            $table->string('type'); // hero, about, services, testimonials, etc.
            $table->text('content')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->string('link_text')->nullable();
            $table->json('extra_data')->nullable(); // For additional fields
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_contents');
    }
};
