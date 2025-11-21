<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageContent extends Model
{
    protected $fillable = [
        'logo',
        'hero_background_image',
        'welcome_title',
        'welcome_subtitle',
        'about_title',
        'about_description',
        'about_features',
        'services_title',
        'services_description',
        'services',
        'is_active',
    ];

    protected $casts = [
        'about_features' => 'array',
        'services' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active landing page content
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first() ?? static::first();
    }
}
