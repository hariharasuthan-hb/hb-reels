<?php

namespace App\Repositories\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Models\CmsContent;

interface CmsContentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find content by type
     */
    public function findByType(string $type): \Illuminate\Support\Collection;

    /**
     * Find content by key
     */
    public function findByKey(string $key): ?CmsContent;

    /**
     * Get content for frontend
     */
    public function getFrontendContent(string $type = null): \Illuminate\Support\Collection;
}

