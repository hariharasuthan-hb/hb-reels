<?php

namespace App\Repositories\Eloquent;

use App\Models\Announcement;
use App\Models\User;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    public function __construct(Announcement $model)
    {
        parent::__construct($model);
    }

    public function createAnnouncement(array $data): Announcement
    {
        return $this->create($data);
    }

    public function updateAnnouncement(Announcement $announcement, array $data): bool
    {
        return $announcement->update($data);
    }

    public function deleteAnnouncement(Announcement $announcement): bool
    {
        return $announcement->delete();
    }

    public function queryForDataTable(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->with(['creator']);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySearch($query, $filters['search'] ?? null);

        return $query;
    }

    public function getRecentForUser(User $user, int $limit = 10): Collection
    {
        return $this->model
            ->newQuery()
            ->published()
            ->forUser($user)
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function getStatusOptions(): array
    {
        return [
            Announcement::STATUS_DRAFT => 'Draft',
            Announcement::STATUS_PUBLISHED => 'Published',
            Announcement::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function getAudienceOptions(): array
    {
        return [
            Announcement::AUDIENCE_ALL => 'Everyone',
            Announcement::AUDIENCE_TRAINER => 'Trainers',
            Announcement::AUDIENCE_MEMBER => 'Members',
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
                ->orWhere('body', 'like', "%{$search}%");
        });
    }
}

