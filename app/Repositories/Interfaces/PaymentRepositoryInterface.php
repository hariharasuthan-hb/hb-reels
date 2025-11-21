<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Retrieve completed payments within the provided date range (inclusive).
     */
    public function getCompletedBetweenDates(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Get distinct payment methods for filter dropdowns.
     */
    public function getDistinctMethods(): Collection;
}

