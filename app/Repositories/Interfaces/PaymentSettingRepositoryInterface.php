<?php

namespace App\Repositories\Interfaces;

use App\Models\PaymentSetting;

interface PaymentSettingRepositoryInterface
{
    /**
     * Get payment settings (singleton - always returns first or creates one).
     */
    public function getSettings(): PaymentSetting;

    /**
     * Update payment settings.
     */
    public function updateSettings(array $data): PaymentSetting;
}

