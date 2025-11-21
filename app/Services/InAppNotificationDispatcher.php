<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class InAppNotificationDispatcher
{
    /**
     * Ensure all intended recipients are attached to a notification.
     */
    public function dispatch(InAppNotification $notification): void
    {
        if (! $notification->isPublished()) {
            return;
        }

        $userIds = $this->resolveAudienceUserIds($notification);

        if ($userIds->isEmpty()) {
            return;
        }

        $syncData = $userIds->mapWithKeys(fn (int $id) => [$id => []])->toArray();

        $notification->recipients()->sync($syncData);
    }

    /**
     * Determine which users should receive the notification.
     */
    protected function resolveAudienceUserIds(InAppNotification $notification): Collection
    {
        return match ($notification->audience_type) {
            InAppNotification::AUDIENCE_ALL => User::query()->pluck('id'),
            InAppNotification::AUDIENCE_TRAINER => User::role('trainer')->pluck('id'),
            InAppNotification::AUDIENCE_MEMBER => User::role('member')->pluck('id'),
            InAppNotification::AUDIENCE_USER => collect(
                $notification->target_user_id ? [$notification->target_user_id] : []
            ),
            default => collect(),
        };
    }
}

