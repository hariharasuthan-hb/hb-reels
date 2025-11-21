<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationCenterController extends Controller
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
        private readonly InAppNotificationRepositoryInterface $notificationRepository
    ) {
    }

    public function index()
    {
        $user = Auth::user();

        $announcements = $this->announcementRepository->getRecentForUser($user);

        $notifications = $this->notificationRepository->getForUser($user, 15);

        return view('admin.notifications.center', [
            'announcements' => $announcements,
            'notifications' => $notifications,
            'showMarkRead' => ! $user->hasRole('admin'),
        ]);
    }

    public function markAsRead(Request $request, InAppNotification $notification)
    {
        $user = $request->user();

        if (! $notification->recipients()->where('user_id', $user->getKey())->exists()) {
            abort(403);
        }

        $notification->markAsReadFor($user);

        if ($request->wantsJson()) {
            $unreadCount = $this->notificationRepository->getUnreadCountForUser($user);

            return response()->json([
                'success' => true,
                'count' => $unreadCount,
            ]);
        }

        return back()->with('success', 'Notification marked as read.');
    }
}

