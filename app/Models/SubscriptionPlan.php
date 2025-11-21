<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_name',
        'description',
        'image',
        'duration_type',
        'duration',
        'price',
        'trial_days',
        'stripe_price_id',
        'razorpay_plan_id',
        'is_active',
        'features',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'duration' => 'integer',
            'trial_days' => 'integer',
            'features' => 'array',
        ];
    }

    /**
     * Get active subscription plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get plans by duration type.
     */
    public function scopeByDurationType($query, string $durationType)
    {
        return $query->where('duration_type', $durationType);
    }

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        return $this->duration . ' ' . ucfirst($this->duration_type) . ($this->duration > 1 ? 's' : '');
    }

    /**
     * Get duration type options for forms.
     */
    public static function getDurationTypeOptions(): array
    {
        return [
            'trial' => '14 days trial',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];
    }

    /**
     * Get valid duration type values for validation.
     * Returns array keys excluding the empty string option.
     */
    public static function getValidDurationTypes(): array
    {
        $options = self::getDurationTypeOptions();
        // Remove empty string key and return only valid values
        unset($options['']);
        return array_keys($options);
    }

    /**
     * Process features for form display.
     * Handles conversion from string/JSON to array and ensures at least one empty field.
     */
    public static function processFeaturesForForm($features = null): array
    {
        // If features is null, return array with one empty string
        if ($features === null) {
            return [''];
        }

        // If features is a string, try to decode it
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = $decoded ?? [];
        }

        // Ensure it's an array
        if (!is_array($features)) {
            $features = [];
        }

        // If empty, return array with one empty string for form
        if (empty($features)) {
            return [''];
        }

        return $features;
    }

    /**
     * Get features from old input or model instance.
     */
    public static function getFeaturesForForm($subscriptionPlan = null): array
    {
        // Check for old input first (validation errors)
        if (old('features') !== null) {
            return self::processFeaturesForForm(old('features'));
        }

        // If model exists, get features from it
        if ($subscriptionPlan && isset($subscriptionPlan->features)) {
            return self::processFeaturesForForm($subscriptionPlan->features);
        }

        // Default: return array with one empty string
        return [''];
    }

    /**
     * Get default is_active value for forms.
     */
    public static function getDefaultIsActive($subscriptionPlan = null): bool
    {
        if (old('is_active') !== null) {
            return (bool) old('is_active');
        }

        if ($subscriptionPlan && isset($subscriptionPlan->is_active)) {
            return (bool) $subscriptionPlan->is_active;
        }

        return true; // Default to active
    }

    /**
     * Check if plan has trial period.
     */
    public function hasTrial(): bool
    {
        return $this->trial_days > 0 || $this->duration_type === 'trial';
    }

    /**
     * Get trial days for this plan.
     */
    public function getTrialDays(): int
    {
        if ($this->duration_type === 'trial') {
            return $this->duration;
        }
        
        return $this->trial_days ?? 0;
    }
}

