<?php

namespace App\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Models\CmsPage;

interface CmsPageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find page by slug
     */
    public function findBySlug(string $slug): ?CmsPage;

    /**
     * Get active pages
     */
    public function getActivePages(): \Illuminate\Support\Collection;
}

