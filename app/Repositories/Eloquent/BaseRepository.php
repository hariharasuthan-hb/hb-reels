<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records with pagination, sorting, and search
     */
    public function all(array $filters = [], array $sort = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply sorting
        $query = $this->applySorting($query, $sort);

        // Apply search
        $query = $this->applySearch($query, $filters['search'] ?? null);

        return $query->paginate($perPage);
    }

    /**
     * Get all records without pagination
     */
    public function getAll(array $filters = [], array $sort = []): Collection
    {
        $query = $this->model->newQuery();

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply sorting
        $query = $this->applySorting($query, $sort);

        // Apply search
        $query = $this->applySearch($query, $filters['search'] ?? null);

        return $query->get();
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find a record by column
     */
    public function findBy(string $column, $value): ?Model
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * Get count of records
     */
    public function count(array $filters = []): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);
        return $query->count();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        // Override in child classes for specific filters
        return $query;
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting($query, array $sort)
    {
        if (empty($sort)) {
            $sort = ['created_at' => 'desc']; // Default sorting
        }

        foreach ($sort as $column => $direction) {
            if (in_array(strtolower($direction), ['asc', 'desc'])) {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Apply search to query
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

        // Override in child classes to specify searchable columns
        return $query;
    }

    /**
     * Get searchable columns (override in child classes)
     */
    protected function getSearchableColumns(): array
    {
        return [];
    }
}

