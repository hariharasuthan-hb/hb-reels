<?php

namespace App\Repositories\Eloquent;

use App\Models\Expense;
use App\Repositories\Interfaces\ExpenseRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpenseRepository extends BaseRepository implements ExpenseRepositoryInterface
{
    public function __construct(Expense $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getBetweenDates(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model
            ->newQuery()
            ->whereBetween('spent_at', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('spent_at')
            ->get(['id', 'category', 'amount', 'spent_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function createExpense(array $data): Expense
    {
        return $this->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateExpense(Expense $expense, array $data): bool
    {
        return $expense->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExpense(Expense $expense): bool
    {
        return $expense->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistinctCategories(): Collection
    {
        return $this->model
            ->newQuery()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Apply category filter.
     */
    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('spent_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('spent_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['vendor'])) {
            $query->where('vendor', 'like', '%' . $filters['vendor'] . '%');
        }

        return $query;
    }

    /**
     * Enable searching by category, vendor, and notes.
     */
    protected function applySearch($query, mixed $search)
    {
        $search = is_array($search) ? ($search['value'] ?? null) : $search;

        if (!is_string($search) || trim($search) === '') {
            return $query;
        }

        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('category', 'like', "%{$search}%")
              ->orWhere('vendor', 'like', "%{$search}%")
              ->orWhere('notes', 'like', "%{$search}%");
        });
    }
}
