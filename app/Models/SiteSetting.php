<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_title',
        'logo',
        'contact_email',
        'contact_mobile',
        'address',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'footer_partner',
    ];

    /**
     * Get the current site settings (singleton pattern)
     */
    public static function getSettings()
    {
        return static::first() ?? static::create([
            'site_title' => 'Gym Management',
        ]);
    }
}
