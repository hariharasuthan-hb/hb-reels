<?php

namespace App\Repositories\Eloquent;

use App\Models\Income;
use App\Repositories\Interfaces\IncomeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IncomeRepository extends BaseRepository implements IncomeRepositoryInterface
{
    public function __construct(Income $model)
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
            ->whereBetween('received_at', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('received_at')
            ->get(['id', 'category', 'amount', 'received_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function createIncome(array $data): Income
    {
        return $this->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateIncome(Income $income, array $data): bool
    {
        return $income->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIncome(Income $income): bool
    {
        return $income->delete();
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
            $query->whereDate('received_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('received_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', 'like', '%' . $filters['source'] . '%');
        }

        return $query;
    }

    /**
     * Enable searching by category, source, and notes.
     */
    protected function applySearch($query, mixed $search)
    {
        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        if (!is_string($search) || trim($search) === '') {
            return $query;
        }

        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('category', 'like', "%{$search}%")
              ->orWhere('source', 'like', "%{$search}%")
              ->orWhere('notes', 'like', "%{$search}%");
        });
    }
}

