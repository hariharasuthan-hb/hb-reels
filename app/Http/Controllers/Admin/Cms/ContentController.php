<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\DataTables\CmsContentDataTable;
use App\Http\Requests\Admin\Cms\StoreContentRequest;
use App\Http\Requests\Admin\Cms\UpdateContentRequest;
use App\Repositories\Interfaces\CmsContentRepositoryInterface;
use App\Services\ImageUploadService;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\PexelsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
    protected AIService $aiService;
    protected PexelsService $pexelsService;

    public function __construct(
        CmsContentRepositoryInterface $repository,
        ImageUploadService $imageService,
        AIService $aiService,
        PexelsService $pexelsService
    ) {
        $this->repository = $repository;
        $this->imageService = $imageService;
        $this->aiService = $aiService;
        $this->pexelsService = $pexelsService;
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

        // HTML checkboxes only submit when checked; make sure "unchecked" persists as false.
        $data['is_active'] = $request->has('is_active');

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
        } elseif ($request->boolean('video_is_background') && !$request->hasFile('video')) {
            // Auto-generate video from caption if video_is_background is enabled and no video uploaded
            $data['video_path'] = $this->generateVideoFromCaption($data);
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

        // HTML checkboxes only submit when checked; make sure "unchecked" persists as false.
        $data['is_active'] = $request->has('is_active');

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
        } elseif ($request->boolean('video_is_background') && !$request->hasFile('video') && !$content->video_path) {
            // Auto-generate video from caption if video_is_background is enabled, no video uploaded, and no existing video
            $data['video_path'] = $this->generateVideoFromCaption($data);
        } elseif ($request->boolean('video_is_background') && !$request->hasFile('video') && $content->video_path) {
            // If content changed and video_is_background is still enabled, regenerate video
            $captionText = $this->buildCaptionText($data);
            $existingCaptionText = $this->buildCaptionText([
                'title' => $content->title,
                'description' => $content->description,
                'content' => $content->content,
            ]);
            
            // Regenerate if caption text changed significantly
            if ($captionText !== $existingCaptionText && !empty($captionText)) {
                $data['video_path'] = $this->generateVideoFromCaption($data, $content->video_path);
            }
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

    /**
     * Generate a video from caption using AI and Pexels.
     * 
     * @param array $data Content data
     * @param string|null $oldVideoPath Path to old video to delete
     * @return string|null Path to generated video or null on failure
     */
    protected function generateVideoFromCaption(array $data, ?string $oldVideoPath = null): ?string
    {
        try {
            // Build caption text from content
            $captionText = $this->buildCaptionText($data);
            
            if (empty($captionText)) {
                Log::warning('CMS Content: Cannot generate video - no caption text available');
                return null;
            }

            // Generate AI caption and video keywords
            $aiResult = $this->aiService->generateCaption($captionText);
            $caption = $aiResult['caption'] ?? $captionText;
            $videoKeywords = $aiResult['video_keywords'] ?? [];

            // Build search query from caption and keywords
            $searchQuery = !empty($videoKeywords) 
                ? implode(' ', array_slice($videoKeywords, 0, 3))
                : $caption;

            // Download relevant video from Pexels
            $downloadedVideoPath = $this->pexelsService->downloadVideo($searchQuery);

            // Move video from temp path to CMS videos directory
            $tempDisk = config('eventreel.storage.disk', 'local');
            $finalPath = 'cms/videos/' . basename($downloadedVideoPath);
            
            // Copy video to final location (use public disk for CMS content)
            if (Storage::disk($tempDisk)->exists($downloadedVideoPath)) {
                $videoContent = Storage::disk($tempDisk)->get($downloadedVideoPath);
                Storage::disk('public')->put($finalPath, $videoContent);
                
                // Delete temp video
                Storage::disk($tempDisk)->delete($downloadedVideoPath);
                
                // Delete old video if provided
                if ($oldVideoPath) {
                    // Try both public and the temp disk
                    if (Storage::disk('public')->exists($oldVideoPath)) {
                        Storage::disk('public')->delete($oldVideoPath);
                    } elseif (Storage::disk($tempDisk)->exists($oldVideoPath)) {
                        Storage::disk($tempDisk)->delete($oldVideoPath);
                    }
                }
                
                Log::info('CMS Content: Successfully generated video from caption', [
                    'caption' => $caption,
                    'search_query' => $searchQuery,
                    'video_path' => $finalPath
                ]);
                
                return $finalPath;
            }

            Log::error('CMS Content: Downloaded video file not found', [
                'downloaded_path' => $downloadedVideoPath
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('CMS Content: Failed to generate video from caption', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Build caption text from content data.
     * 
     * @param array $data Content data
     * @return string Caption text
     */
    protected function buildCaptionText(array $data): string
    {
        $textParts = [];
        
        // Priority: title > description > content
        if (!empty($data['title'])) {
            $textParts[] = $data['title'];
        }
        
        if (!empty($data['description'])) {
            $textParts[] = strip_tags($data['description']);
        }
        
        if (!empty($data['content'])) {
            // Strip HTML tags and get first 200 characters
            $content = strip_tags($data['content']);
            $content = preg_replace('/\s+/', ' ', $content);
            $textParts[] = mb_substr(trim($content), 0, 200);
        }
        
        return implode(' ', $textParts);
    }
}
