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
        Schema::create('landing_page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('hero_background_image')->nullable();
            $table->string('welcome_title')->default('Welcome to Our Gym');
            $table->text('welcome_subtitle')->nullable();
            $table->string('about_title')->default('About Us');
            $table->text('about_description')->nullable();
            $table->text('about_features')->nullable(); // JSON for features
            $table->string('services_title')->default('Our Services');
            $table->text('services_description')->nullable();
            $table->text('services')->nullable(); // JSON for services
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_contents');
    }
};
