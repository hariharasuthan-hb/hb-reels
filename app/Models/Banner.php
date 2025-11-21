<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'link',
        'link_text',
        'overlay_color',
        'overlay_opacity',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'overlay_opacity' => 'decimal:2',
    ];

    /**
     * Get active banners ordered by order
     */
    public static function getActiveBanners()
    {
        return static::where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
