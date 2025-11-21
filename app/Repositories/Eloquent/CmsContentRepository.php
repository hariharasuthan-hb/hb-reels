<?php

namespace App\Repositories\Eloquent;

use App\Models\CmsContent;
use App\Repositories\Interfaces\CmsContentRepositoryInterface;

class CmsContentRepository extends BaseRepository implements CmsContentRepositoryInterface
{
    public function __construct(CmsContent $model)
    {
        parent::__construct($model);
    }

    /**
     * Find content by type
     */
    public function findByType(string $type): \Illuminate\Support\Collection
    {
        return $this->model->where('type', $type)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Find content by key
     */
    public function findByKey(string $key): ?CmsContent
    {
        return $this->model->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get content for frontend
     */
    public function getFrontendContent(string $type = null): \Illuminate\Support\Collection
    {
        $query = $this->model->where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('order', 'asc')->get();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
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

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('key', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Get searchable columns
     */
    protected function getSearchableColumns(): array
    {
        return ['title', 'key', 'content', 'type'];
    }
}

