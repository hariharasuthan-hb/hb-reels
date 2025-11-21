<?php

namespace App\Repositories\Interfaces;

use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface IncomeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Retrieve incomes between the provided dates (inclusive).
     */
    public function getBetweenDates(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Create a new income record.
     */
    public function createIncome(array $data): Income;

    /**
     * Update an existing income record.
     */
    public function updateIncome(Income $income, array $data): bool;

    /**
     * Delete the provided income.
     */
    public function deleteIncome(Income $income): bool;

    /**
     * Get distinct list of categories for filter dropdowns.
     */
    public function getDistinctCategories(): Collection;
}

