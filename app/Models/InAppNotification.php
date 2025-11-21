<?php

namespace App\Models;

use App\Models\Pivots\NotificationRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InAppNotification extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_TRAINER = 'trainer';
    public const AUDIENCE_MEMBER = 'member';
    public const AUDIENCE_USER = 'user';

    protected $fillable = [
        'title',
        'message',
        'audience_type',
        'target_user_id',
        'status',
        'scheduled_for',
        'published_at',
        'expires_at',
        'requires_acknowledgement',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'requires_acknowledgement' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $publishedQuery) {
                $publishedQuery->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $expiryQuery) {
                $expiryQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where(function (Builder $visibilityQuery) use ($user) {
            $visibilityQuery->where('audience_type', self::AUDIENCE_ALL);

            if ($user->hasRole('trainer')) {
                $visibilityQuery->orWhere('audience_type', self::AUDIENCE_TRAINER);
            }

            if ($user->hasRole('member')) {
                $visibilityQuery->orWhere('audience_type', self::AUDIENCE_MEMBER);
            }

            $visibilityQuery->orWhere(function (Builder $subQuery) use ($user) {
                $subQuery->where('audience_type', self::AUDIENCE_USER)
                    ->where('target_user_id', $user->getKey());
            });
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

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'notification_user')
            ->withPivot(['read_at', 'dismissed_at'])
            ->withTimestamps()
            ->using(NotificationRecipient::class);
    }

    public function markAsReadFor(User $user): void
    {
        $this->recipients()->syncWithoutDetaching([
            $user->getKey() => ['read_at' => now()],
        ]);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

}

