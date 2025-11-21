<?php

namespace App\Repositories\Eloquent;

use App\Models\InAppNotification;
use App\Models\User;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class InAppNotificationRepository extends BaseRepository implements InAppNotificationRepositoryInterface
{
    public function __construct(InAppNotification $model)
    {
        parent::__construct($model);
    }

    public function createNotification(array $data): InAppNotification
    {
        return $this->create($data);
    }

    public function updateNotification(InAppNotification $notification, array $data): bool
    {
        return $notification->update($data);
    }

    public function deleteNotification(InAppNotification $notification): bool
    {
        return $notification->delete();
    }

    public function queryForDataTable(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->with(['creator', 'targetUser']);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySearch($query, $filters['search'] ?? null);

        return $query;
    }

    public function getForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        if ($user->hasRole('admin')) {
            return $this->model->newQuery()
                ->orderByDesc('published_at')
                ->paginate($perPage);
        }

        return $user->receivedNotifications()
            ->wherePivotNull('dismissed_at')
            ->published()
            ->orderByDesc('in_app_notifications.published_at')
            ->paginate($perPage);
    }

    public function getUnreadCountForUser(User $user): int
    {
        if ($user->hasRole('admin')) {
            return $this->model->newQuery()
                ->published()
                ->count();
        }

        return $user->receivedNotifications()
            ->wherePivotNull('dismissed_at')
            ->wherePivotNull('read_at')
            ->published()
            ->count();
    }

    public function getStatusOptions(): array
    {
        return [
            InAppNotification::STATUS_DRAFT => 'Draft',
            InAppNotification::STATUS_SCHEDULED => 'Scheduled',
            InAppNotification::STATUS_PUBLISHED => 'Published',
            InAppNotification::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function getAudienceOptions(): array
    {
        return [
            InAppNotification::AUDIENCE_ALL => 'Everyone',
            InAppNotification::AUDIENCE_TRAINER => 'Trainers',
            InAppNotification::AUDIENCE_MEMBER => 'Members',
            InAppNotification::AUDIENCE_USER => 'Specific User',
        ];
    }

    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['audience_type'])) {
            $query->where('audience_type', $filters['audience_type']);
        }

        if (!empty($filters['requires_acknowledgement'])) {
            $query->where('requires_acknowledgement', filter_var($filters['requires_acknowledgement'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['scheduled_from'])) {
            $query->whereDate('scheduled_for', '>=', $filters['scheduled_from']);
        }

        if (!empty($filters['scheduled_to'])) {
            $query->whereDate('scheduled_for', '<=', $filters['scheduled_to']);
        }

        if (!empty($filters['published_from'])) {
            $query->whereDate('published_at', '>=', $filters['published_from']);
        }

        if (!empty($filters['published_to'])) {
            $query->whereDate('published_at', '<=', $filters['published_to']);
        }

        return $query;
    }

    protected function applySearch($query, mixed $search)
    {
        $search = is_array($search) ? ($search['value'] ?? null) : $search;

        if (!is_string($search) || trim($search) === '') {
            return $query;
        }

        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%");
        });
    }
}

