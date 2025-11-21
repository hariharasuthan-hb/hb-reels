<?php

namespace App\Repositories\Interfaces;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ExpenseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Retrieve expenses between the provided dates (inclusive).
     */
    public function getBetweenDates(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Create a new expense record.
     */
    public function createExpense(array $data): Expense;

    /**
     * Update an existing expense record.
     */
    public function updateExpense(Expense $expense, array $data): bool;

    /**
     * Delete the provided expense.
     */
    public function deleteExpense(Expense $expense): bool;

    /**
     * Get distinct list of categories for filter dropdowns.
     */
    public function getDistinctCategories(): Collection;
}

