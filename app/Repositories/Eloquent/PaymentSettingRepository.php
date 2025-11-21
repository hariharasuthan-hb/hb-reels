<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentSetting;
use App\Repositories\Interfaces\PaymentSettingRepositoryInterface;

class PaymentSettingRepository implements PaymentSettingRepositoryInterface
{
    public function __construct(
        private readonly PaymentSetting $model
    ) {
    }

    /**
     * Get payment settings (singleton - always returns first or creates one).
     */
    public function getSettings(): PaymentSetting
    {
        return PaymentSetting::getSettings();
    }

    /**
     * Update payment settings.
     */
    public function updateSettings(array $data): PaymentSetting
    {
        $settings = $this->getSettings();
        $settings->update($data);
        return $settings->fresh();
    }
}

