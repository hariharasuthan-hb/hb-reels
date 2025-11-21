<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'title',
        'url',
        'route',
        'icon',
        'order',
        'is_active',
        'target',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get active menus ordered by order
     */
    public static function getActiveMenus()
    {
        return static::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Get the full URL for the menu item
     */
    public function getFullUrlAttribute()
    {
        if ($this->route) {
            // Check if route requires authentication and user is not authenticated
            if ($this->route === 'member.dashboard' && !auth()->check()) {
                return route('login');
            }
            return route($this->route);
        }
        
        return $this->url ?? '#';
    }
}
