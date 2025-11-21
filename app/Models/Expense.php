<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use HasFactory;

    /**
     * Payment method constants
     */
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_DEBIT_CARD = 'debit_card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_CHECK = 'check';
    public const METHOD_OTHER = 'other';

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
            self::METHOD_CHECK,
            self::METHOD_OTHER,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category',
        'vendor',
        'amount',
        'spent_at',
        'payment_method',
        'reference',
        'reference_document_path',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'spent_at' => 'date',
        ];
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
     * Get the public URL for the uploaded reference document.
     */
    public function getReferenceDocumentUrlAttribute(): ?string
    {
        if (!$this->reference_document_path) {
            return null;
        }

        return Storage::disk('public')->url($this->reference_document_path);
    }
}

