<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsContent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'key',
        'type',
        'content',
        'description',
        'image',
        'background_image',
        'video_path',
        'link',
        'link_text',
        'extra_data',
        'order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'extra_data' => 'array',
    ];

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
