<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\DataTables\CmsContentDataTable;
use App\Http\Requests\Admin\Cms\StoreContentRequest;
use App\Http\Requests\Admin\Cms\UpdateContentRequest;
use App\Repositories\Interfaces\CmsContentRepositoryInterface;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing CMS content in the admin panel.
 * 
 * Handles CRUD operations for CMS content items including creation, updating,
 * deletion, and viewing. CMS content is used for managing reusable content
 * blocks displayed on the frontend website (e.g., hero sections, features,
 * testimonials).
 */
class ContentController extends Controller
{
    protected CmsContentRepositoryInterface $repository;
    protected ImageUploadService $imageService;

    public function __construct(
        CmsContentRepositoryInterface $repository,
        ImageUploadService $imageService
    ) {
        $this->repository = $repository;
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CmsContentDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new \App\Models\CmsContent))->toJson();
        }

        return view('admin.cms.content.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.cms.content.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload(
                $request->file('image'),
                'cms/content'
            );
        }

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            $data['background_image'] = $this->imageService->upload(
                $request->file('background_image'),
                'cms/content'
            );
        }

        // Handle video upload (e.g., testimonials)
        if ($request->hasFile('video')) {
            $data['video_path'] = $this->imageService->upload(
                $request->file('video'),
                'cms/videos'
            );
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $this->repository->create($data);

        return redirect()->route('admin.cms.content.index')
            ->with('success', 'Content created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): View
    {
        $content = $this->repository->findOrFail($id);
        return view('admin.cms.content.show', compact('content'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $content = $this->repository->findOrFail($id);
        return view('admin.cms.content.edit', compact('content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContentRequest $request, int $id): RedirectResponse
    {
        $content = $this->repository->findOrFail($id);
        $data = $request->validated();

        // Remove image if requested
        if ($request->boolean('remove_image')) {
            if ($content->image) {
                $this->imageService->delete($content->image);
            }
            $data['image'] = null;
        }

        // Remove background image if requested
        if ($request->boolean('remove_background_image')) {
            if ($content->background_image) {
                $this->imageService->delete($content->background_image);
            }
            $data['background_image'] = null;
        }

        // Remove video if requested
        if ($request->boolean('remove_video')) {
            if ($content->video_path) {
                $this->imageService->delete($content->video_path);
            }
            $data['video_path'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload(
                $request->file('image'),
                'cms/content',
                $content->image
            );
        }

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            $data['background_image'] = $this->imageService->upload(
                $request->file('background_image'),
                'cms/content',
                $content->background_image
            );
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            $data['video_path'] = $this->imageService->upload(
                $request->file('video'),
                'cms/videos',
                $content->video_path
            );
        }

        $data['updated_by'] = auth()->id();

        $this->repository->update($id, $data);

        return redirect()->route('admin.cms.content.index')
            ->with('success', 'Content updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $content = $this->repository->findOrFail($id);

        // Delete images if exist
        if ($content->image) {
            $this->imageService->delete($content->image);
        }
        if ($content->background_image) {
            $this->imageService->delete($content->background_image);
        }
        if ($content->video_path) {
            $this->imageService->delete($content->video_path);
        }

        $this->repository->delete($id);

        return redirect()->route('admin.cms.content.index')
            ->with('success', 'Content deleted successfully.');
    }
}
