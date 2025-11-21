<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory;

    /**
     * Export status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Export type constants
     */
    public const TYPE_PAYMENTS = 'payments';
    public const TYPE_INVOICES = 'invoices';
    public const TYPE_EXPENSES = 'expenses';
    public const TYPE_INCOMES = 'incomes';
    public const TYPE_SUBSCRIPTIONS = 'subscriptions';
    public const TYPE_ACTIVITY_LOGS = 'activity_logs';
    public const TYPE_FINANCES = 'finances';

    /**
     * Format constants
     */
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'export_type',
        'filename',
        'filepath',
        'format',
        'filters',
        'status',
        'error_message',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the export.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the download URL for the export file.
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->filepath || $this->status !== self::STATUS_COMPLETED) {
            return null;
        }

        return route('admin.exports.download', $this->id);
    }

    /**
     * Check if export is ready for download.
     */
    public function isReady(): bool
    {
        return $this->status === self::STATUS_COMPLETED && !empty($this->filepath);
    }

    /**
     * Check if export failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}

