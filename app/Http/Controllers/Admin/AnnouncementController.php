<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\AnnouncementDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use App\Services\EntityIntegrityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Manage announcements shown to trainers and members inside the portal.
 */
class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
        private readonly EntityIntegrityService $entityIntegrityService
    ) {
        $this->middleware('permission:view announcements')->only(['index']);
        $this->middleware('permission:create announcements')->only(['create', 'store']);
        $this->middleware('permission:edit announcements')->only(['edit', 'update']);
        $this->middleware('permission:delete announcements')->only(['destroy']);
    }

    public function index(AnnouncementDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Announcement()))->toJson();
        }

        return view('admin.announcements.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only(['search', 'status', 'audience_type', 'published_from', 'published_to']),
            'statusOptions' => $this->announcementRepository->getStatusOptions(),
            'audienceOptions' => $this->announcementRepository->getAudienceOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->announcementRepository->createAnnouncement(
            $this->transformPayload($request->validated(), true)
        );

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->announcementRepository->updateAnnouncement(
            $announcement,
            $this->transformPayload($request->validated(), false)
        );

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($reason = $this->entityIntegrityService->firstAnnouncementDeletionBlocker($announcement)) {
            return redirect()
                ->route('admin.announcements.index')
                ->with('error', $reason);
        }

        $this->announcementRepository->deleteAnnouncement($announcement);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement removed.');
    }

    protected function transformPayload(array $data, bool $isCreate): array
    {
        $now = now();

        if (($data['status'] ?? Announcement::STATUS_DRAFT) === Announcement::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = $now;
        }

        $data['expires_at'] = $data['expires_at'] ?? null;

        $userId = Auth::id();

        if ($isCreate) {
            $data['created_by'] = $userId;
        }

        $data['updated_by'] = $userId;

        return $data;
    }
}

