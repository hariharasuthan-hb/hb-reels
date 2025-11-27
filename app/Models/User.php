<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'age',
        'gender',
        'address',
        'qr_code',
        'rfid_card',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'string',
        ];
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include members with an active subscription.
     */
    public function scopeSubscribed($query)
    {
        return $query->active()->whereHas('activeSubscription');
    }

    /**
     * Determine if the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * Get the active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->hasOne(\App\Models\Subscription::class)
            ->whereIn('status', [
                \App\Models\Subscription::STATUS_ACTIVE,
                \App\Models\Subscription::STATUS_TRIALING,
            ])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                    ->orWhere('next_billing_at', '>=', now());
            })
            ->latest('next_billing_at');
    }

    /**
     * Check if user has an active subscription (including trial).
     */
    public function hasActiveSubscription(): bool
    {
        $subscription = $this->activeSubscription;
        return $subscription && $subscription->hasAccess();
    }

    /**
     * Check if user is on trial.
     */
    public function isOnTrial(): bool
    {
        $subscription = $this->activeSubscription;
        return $subscription && $subscription->isTrialing();
    }

    /**
     * Check if user can access subscription features.
     * Returns true if user is admin OR has active subscription.
     */
    public function canAccessSubscriptionFeatures(): bool
    {
        // Admins can always access
        if ($this->hasRole('admin')) {
            return true;
        }

        // Check if user is active and has valid subscription
        return $this->isActive() && $this->hasActiveSubscription();
    }

    /**
     * Notifications assigned to the user.
     */
    public function receivedNotifications()
    {
        return $this->belongsToMany(InAppNotification::class, 'notification_user')
            ->withPivot(['read_at', 'dismissed_at'])
            ->withTimestamps();
    }
}
