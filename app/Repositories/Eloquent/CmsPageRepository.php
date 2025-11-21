<?php

namespace App\Repositories\Eloquent;

use App\Models\CmsPage;
use App\Repositories\Interfaces\CmsPageRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CmsPageRepository extends BaseRepository implements CmsPageRepositoryInterface
{
    public function __construct(CmsPage $model)
    {
        parent::__construct($model);
    }

    /**
     * Find page by slug
     */
    public function findBySlug(string $slug): ?CmsPage
    {
        return $this->model->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active pages
     */
    public function getActivePages(): \Illuminate\Support\Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
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
              ->orWhere('slug', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('meta_description', 'like', "%{$search}%");
        });
    }

    /**
     * Get searchable columns
     */
    protected function getSearchableColumns(): array
    {
        return ['title', 'slug', 'content', 'meta_description'];
    }
}

