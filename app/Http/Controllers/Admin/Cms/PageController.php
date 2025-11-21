<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\DataTables\CmsPageDataTable;
use App\Http\Requests\Admin\Cms\StorePageRequest;
use App\Http\Requests\Admin\Cms\UpdatePageRequest;
use App\Repositories\Interfaces\CmsPageRepositoryInterface;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing CMS pages in the admin panel.
 * 
 * Handles CRUD operations for CMS pages including creation, updating,
 * deletion, and viewing. CMS pages are used for creating custom content
 * pages on the frontend website.
 */
class PageController extends Controller
{
    protected CmsPageRepositoryInterface $repository;
    protected ImageUploadService $imageService;

    public function __construct(
        CmsPageRepositoryInterface $repository,
        ImageUploadService $imageService
    ) {
        $this->repository = $repository;
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CmsPageDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new \App\Models\CmsPage))->toJson();
        }

        return view('admin.cms.pages.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.cms.pages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->imageService->upload(
                $request->file('featured_image'),
                'cms/pages'
            );
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $this->repository->create($data);

        return redirect()->route('admin.cms.pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): View
    {
        $page = $this->repository->findOrFail($id);
        return view('admin.cms.pages.show', compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $page = $this->repository->findOrFail($id);
        return view('admin.cms.pages.edit', compact('page'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, int $id): RedirectResponse
    {
        $page = $this->repository->findOrFail($id);
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->imageService->upload(
                $request->file('featured_image'),
                'cms/pages',
                $page->featured_image
            );
        }

        $data['updated_by'] = auth()->id();

        $this->repository->update($id, $data);

        return redirect()->route('admin.cms.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $page = $this->repository->findOrFail($id);

        // Delete image if exists
        if ($page->featured_image) {
            $this->imageService->delete($page->featured_image);
        }

        $this->repository->delete($id);

        return redirect()->route('admin.cms.pages.index')
            ->with('success', 'Page deleted successfully.');
    }
}
