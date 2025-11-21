<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get query builder with eager-loaded relations for listing.
     */
    public function queryWithRoles(): Builder;

    /**
     * Create a user and optionally assign a role.
     */
    public function createWithRole(array $data): User;

    /**
     * Update a user instance and sync its role assignment.
     */
    public function updateWithRole(User $user, array $data): bool;

    /**
     * Delete a user model instance.
     */
    public function deleteByModel(User $user): bool;
}

