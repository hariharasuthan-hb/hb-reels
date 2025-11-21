<?php

namespace App\Repositories\Interfaces;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface AnnouncementRepositoryInterface extends BaseRepositoryInterface
{
    public function createAnnouncement(array $data): Announcement;

    public function updateAnnouncement(Announcement $announcement, array $data): bool;

    public function deleteAnnouncement(Announcement $announcement): bool;

    public function queryForDataTable(array $filters = []): Builder;

    public function getRecentForUser(User $user, int $limit = 10): Collection;

    public function getStatusOptions(): array;

    public function getAudienceOptions(): array;
}

