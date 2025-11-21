<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'enable_stripe',
        'stripe_publishable_key',
        'stripe_secret_key',
        'enable_razorpay',
        'razorpay_key_id',
        'razorpay_key_secret',
        'enable_gpay',
        'gpay_upi_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enable_stripe' => 'boolean',
            'enable_razorpay' => 'boolean',
            'enable_gpay' => 'boolean',
        ];
    }

    /**
     * Get or create the payment settings (singleton pattern).
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'enable_stripe' => false,
            'enable_razorpay' => false,
            'enable_gpay' => false,
        ]);
    }

    /**
     * Encrypt secret key before saving.
     */
    public function setStripeSecretKeyAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['stripe_secret_key'] = null;
            return;
        }
        
        // Only encrypt if it's not already encrypted (check if it starts with base64 pattern)
        // This prevents double encryption when updating
        try {
            Crypt::decryptString($value);
            // If decrypt succeeds, it's already encrypted, so store as-is
            $this->attributes['stripe_secret_key'] = $value;
        } catch (\Exception $e) {
            // If decrypt fails, it's plain text, so encrypt it
            $this->attributes['stripe_secret_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt secret key when retrieving.
     */
    public function getStripeSecretKeyAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails, return the value as-is (might be plain text from old data)
            return $value;
        }
    }

    /**
     * Encrypt secret key before saving.
     */
    public function setRazorpayKeySecretAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['razorpay_key_secret'] = null;
            return;
        }
        
        // Only encrypt if it's not already encrypted
        try {
            Crypt::decryptString($value);
            // If decrypt succeeds, it's already encrypted, so store as-is
            $this->attributes['razorpay_key_secret'] = $value;
        } catch (\Exception $e) {
            // If decrypt fails, it's plain text, so encrypt it
            $this->attributes['razorpay_key_secret'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt secret key when retrieving.
     */
    public function getRazorpayKeySecretAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails, return the value as-is (might be plain text from old data)
            return $value;
        }
    }
}
