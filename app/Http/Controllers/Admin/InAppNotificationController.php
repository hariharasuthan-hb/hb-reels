<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\InAppNotificationDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInAppNotificationRequest;
use App\Http\Requests\Admin\UpdateInAppNotificationRequest;
use App\Models\InAppNotification;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use App\Services\InAppNotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD controller for in-app notifications managed by admins.
 */
class InAppNotificationController extends Controller
{
    public function __construct(
        private readonly InAppNotificationDispatcher $dispatcher,
        private readonly InAppNotificationRepositoryInterface $notificationRepository
    ) {
        $this->middleware('permission:view notifications')->only(['index']);
        $this->middleware('permission:create notifications')->only(['create', 'store']);
        $this->middleware('permission:edit notifications')->only(['edit', 'update']);
        $this->middleware('permission:delete notifications')->only(['destroy']);
    }

    public function index(InAppNotificationDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new InAppNotification()))->toJson();
        }

        return view('admin.notifications.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only([
                'search',
                'status',
                'audience_type',
                'requires_acknowledgement',
                'scheduled_from',
                'scheduled_to',
                'published_from',
                'published_to',
            ]),
            'statusOptions' => $this->notificationRepository->getStatusOptions(),
            'audienceOptions' => $this->notificationRepository->getAudienceOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.notifications.create', [
            'users' => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreInAppNotificationRequest $request): RedirectResponse
    {
        $notification = $this->notificationRepository->createNotification(
            $this->transformPayload($request->validated(), true)
        );

        $this->dispatcher->dispatch($notification);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Notification created successfully.');
    }

    public function edit(InAppNotification $notification): View
    {
        return view('admin.notifications.edit', [
            'notification' => $notification,
            'users' => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateInAppNotificationRequest $request, InAppNotification $notification): RedirectResponse
    {
        $this->notificationRepository->updateNotification(
            $notification,
            $this->transformPayload($request->validated(), false)
        );

        $this->dispatcher->dispatch($notification);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Notification updated successfully.');
    }

    public function destroy(InAppNotification $notification): RedirectResponse
    {
        $this->notificationRepository->deleteNotification($notification);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Notification removed.');
    }

    protected function transformPayload(array $data, bool $isCreate): array
    {
        $now = now();
        $status = $data['status'] ?? InAppNotification::STATUS_DRAFT;

        if ($status === InAppNotification::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = $now;
        }

        if ($status === InAppNotification::STATUS_SCHEDULED && empty($data['scheduled_for'])) {
            $data['scheduled_for'] = $now;
        }

        if (($data['audience_type'] ?? null) !== InAppNotification::AUDIENCE_USER) {
            $data['target_user_id'] = null;
        }

        $data['expires_at'] = $data['expires_at'] ?? null;
        $data['requires_acknowledgement'] = $data['requires_acknowledgement'] ?? false;

        $userId = Auth::id();

        if ($isCreate) {
            $data['created_by'] = $userId;
        }

        $data['updated_by'] = $userId;

        return $data;
    }
}

