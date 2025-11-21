<?php

namespace App\Repositories\Interfaces;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface InAppNotificationRepositoryInterface extends BaseRepositoryInterface
{
    public function createNotification(array $data): InAppNotification;

    public function updateNotification(InAppNotification $notification, array $data): bool;

    public function deleteNotification(InAppNotification $notification): bool;

    public function queryForDataTable(array $filters = []): Builder;

    public function getForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function getUnreadCountForUser(User $user): int;

    public function getStatusOptions(): array;

    public function getAudienceOptions(): array;
}

