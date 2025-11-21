<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Default attributes for new activity logs.
     */
    protected $attributes = [
        'workout_summary' => null,
        'duration_minutes' => 0,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'workout_summary',
        'duration_minutes',
        'calories_burned',
        'exercises_done',
        'performance_metrics',
        'check_in_method',
        'checked_in_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'exercises_done' => 'array',
            'performance_metrics' => 'array',
            'duration_minutes' => 'integer',
            'calories_burned' => 'decimal:2',
        ];
    }

    /**
     * Get the user (member) for this activity log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who checked in the member.
     */
    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Scope a query to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope logs for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get today's activity log for a user (latest check-in).
     */
    public static function todayForUser(int $userId): ?self
    {
        return static::forUser($userId)
            ->forDate(now()->toDateString())
            ->latest('check_in_time')
            ->first();
    }

}

