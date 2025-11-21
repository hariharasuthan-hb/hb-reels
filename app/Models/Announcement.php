<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Announcement extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_TRAINER = 'trainer';
    public const AUDIENCE_MEMBER = 'member';

    protected $fillable = [
        'title',
        'body',
        'audience_type',
        'status',
        'published_at',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope published announcements.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $dateQuery) {
                $dateQuery->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $expiryQuery) {
                $expiryQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Scope announcements visible to a user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where(function (Builder $audienceQuery) use ($user) {
            $audienceQuery->where('audience_type', self::AUDIENCE_ALL);

            if ($user->hasRole('trainer')) {
                $audienceQuery->orWhere('audience_type', self::AUDIENCE_TRAINER);
            }

            if ($user->hasRole('member')) {
                $audienceQuery->orWhere('audience_type', self::AUDIENCE_MEMBER);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}


