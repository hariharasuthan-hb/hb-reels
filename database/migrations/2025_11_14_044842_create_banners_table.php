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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->string('link_text')->nullable()->default('Learn More');
            $table->string('overlay_color')->nullable()->default('#000000');
            $table->decimal('overlay_opacity', 3, 2)->nullable()->default(0.5);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
