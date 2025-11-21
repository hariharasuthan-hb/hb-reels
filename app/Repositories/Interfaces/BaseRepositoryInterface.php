<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Get all records with pagination, sorting, and search
     */
    public function all(array $filters = [], array $sort = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all records without pagination
     */
    public function getAll(array $filters = [], array $sort = []): Collection;

    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Find a record by column
     */
    public function findBy(string $column, $value): ?Model;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record
     */
    public function delete(int $id): bool;

    /**
     * Get count of records
     */
    public function count(array $filters = []): int;
}

