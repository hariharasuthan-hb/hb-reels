<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * Payment status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Payment method constants
     */
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_DEBIT_CARD = 'debit_card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_RAZORPAY = 'razorpay';
    public const METHOD_STRIPE = 'stripe';

    /**
     * Get all available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }

    /**
     * Get all available payment method options
     */
    public static function getPaymentMethodOptions(): array
    {
        return [
            self::METHOD_CREDIT_CARD,
            self::METHOD_DEBIT_CARD,
            self::METHOD_BANK_TRANSFER,
            self::METHOD_CASH,
            self::METHOD_PAYPAL,
            self::METHOD_RAZORPAY,
            self::METHOD_STRIPE,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'payment_details',
        'promotional_code',
        'discount_amount',
        'final_amount',
        'paid_at',
        // Accounting system columns
        'tenant_id',
        'invoice_id',
        'customer_id',
        'date',
        'is_credit',
        'currency_code',
        'exchange_rate',
        'inverse',
        'method',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $casts = [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'payment_details' => 'array',
        ];
        
        // Only cast paid_at if the column exists
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'paid_at')) {
            $casts['paid_at'] = 'datetime';
        }
        
        return $casts;
    }

    /**
     * Get the date column name to use for payment date.
     * Falls back to created_at if paid_at doesn't exist.
     */
    public static function getDateColumn(): string
    {
        $instance = new static();
        if (\Illuminate\Support\Facades\Schema::hasColumn($instance->getTable(), 'paid_at')) {
            return 'paid_at';
        }
        return 'created_at';
    }

    /**
     * Check if the status column exists in the payments table.
     */
    public static function hasStatusColumn(): bool
    {
        $instance = new static();
        return \Illuminate\Support\Facades\Schema::hasColumn($instance->getTable(), 'status');
    }

    /**
     * Check if the payment_method column exists in the payments table.
     */
    public static function hasPaymentMethodColumn(): bool
    {
        $instance = new static();
        return \Illuminate\Support\Facades\Schema::hasColumn($instance->getTable(), 'payment_method');
    }

    /**
     * Check if the transaction_id column exists in the payments table.
     */
    public static function hasTransactionIdColumn(): bool
    {
        $instance = new static();
        return \Illuminate\Support\Facades\Schema::hasColumn($instance->getTable(), 'transaction_id');
    }

    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription associated with the payment.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Scope a query to only include completed payments.
     */
    public function scopeCompleted($query)
    {
        if (static::hasStatusColumn()) {
            return $query->where('status', 'completed');
        }
        return $query;
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        if (static::hasStatusColumn()) {
            return $query->where('status', 'pending');
        }
        return $query;
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        if (!static::hasStatusColumn()) {
            return true; // If no status column, assume all payments are completed
        }
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        if (!static::hasStatusColumn()) {
            return false; // If no status column, assume no payments are pending
        }
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        if (!static::hasStatusColumn()) {
            return false; // If no status column, assume no payments failed
        }
        return $this->status === 'failed';
    }

    /**
     * Get the readable payment method name.
     * Converts "credit_card" to "Credit Card"
     */
    public function getReadablePaymentMethodAttribute(): string
    {
        if (!$this->payment_method) {
            return '—';
        }

        return ucwords(str_replace('_', ' ', $this->payment_method));
    }

    /**
     * Get final_amount attribute with fallback to amount.
     * Handles cases where final_amount column doesn't exist in the database.
     */
    public function getFinalAmountAttribute($value)
    {
        // If final_amount exists in database, return it
        if ($value !== null) {
            return $value;
        }
        
        // Fallback to amount if final_amount is null or doesn't exist
        return $this->attributes['amount'] ?? $this->amount ?? 0;
    }

    /**
     * Get paid_at attribute with fallback to created_at.
     * Handles cases where paid_at column doesn't exist in the database.
     */
    public function getPaidAtAttribute($value)
    {
        // If paid_at exists in database and has a value, ensure it's a Carbon instance
        if ($value !== null) {
            // If it's already a Carbon instance, return it
            if ($value instanceof \Carbon\Carbon) {
                return $value;
            }
            // If it's a string, parse it
            if (is_string($value)) {
                return \Carbon\Carbon::parse($value);
            }
            return $value;
        }
        
        // Fallback to created_at if paid_at is null or doesn't exist
        $dateColumn = static::getDateColumn();
        if ($dateColumn !== 'paid_at' && isset($this->attributes[$dateColumn])) {
            $fallbackValue = $this->attributes[$dateColumn];
            if (is_string($fallbackValue)) {
                return \Carbon\Carbon::parse($fallbackValue);
            }
            return $fallbackValue;
        }
        
        return $this->created_at ?? null;
    }
}

