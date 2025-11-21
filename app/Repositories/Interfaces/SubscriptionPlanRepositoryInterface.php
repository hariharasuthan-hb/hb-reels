<?php

namespace App\Repositories\Interfaces;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Builder;

interface SubscriptionPlanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get query builder for active plans.
     */
    public function queryActive(): Builder;

    /**
     * Get plans by duration type.
     */
    public function queryByDurationType(string $durationType): Builder;
}

