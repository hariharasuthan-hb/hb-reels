<?php

namespace HbReels\EventReelGenerator\Controllers;

use App\Models\ActivityLog;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
use HbReels\EventReelGenerator\Jobs\DeleteVideoFile;
use HbReels\EventReelGenerator\Jobs\GenerateVideoReel;
use HbReels\EventReelGenerator\Jobs\CleanupDownloadedVideo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReelController
{
    public function __construct()
    {
    }

    /**
     * Show the event reel generator form.
     */
    public function index()
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access the video generator.');
        }

        if (!auth()->user()->hasRole('member') && !auth()->user()->hasRole('admin')) {
            return redirect()->route('frontend.home')->with('error', 'Access denied. Member or admin access required.');
        }

        // Check subscription only if subscriptions are enabled
        if (config('app.enable_subscription', env('ENABLE_SUBSCRIPTION', false))) {
            // Skip subscription check for admins
            if (!auth()->user()->hasRole('admin') && !auth()->user()->hasActiveSubscription()) {
                return redirect()->route('member.subscriptions')->with('error', 'You need an active subscription to generate videos. Please subscribe to continue.');
            }
        }

        return view('eventreel::index');
    }

    /**
     * Check video generation status and return download URL if ready.
     */
    public function checkStatus()
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            \Log::warning('Status check failed: user not authenticated');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the timestamp of the current generation session
        $generationTimestamp = session('video_generation_timestamp');

        // Build query for completed videos
        $query = ActivityLog::where('user_id', auth()->id())
            ->where('activity_type', 'event_reel_generation')
            ->where('status', 'completed')
            ->whereNotNull('video_path');

        // If we have a generation timestamp, only check videos created after it
        if ($generationTimestamp) {
            $query->where('created_at', '>=', $generationTimestamp);
        } else {
            // Fallback to last 24 hours if no session timestamp
            $query->where('created_at', '>=', now()->subDay());
        }

        $completedVideos = $query->latest()
            ->get()
            ->filter(function ($video) {
                // Verify file still exists
                $exists = Storage::disk(config('eventreel.storage.disk'))->exists($video->video_path);
                return $exists;
            });

        if ($completedVideos->isNotEmpty()) {
            $latestVideo = $completedVideos->first();

            // Don't schedule cleanup here - it will be scheduled when download actually starts
            return response()->json([
                'status' => 'ready',
                'download_url' => route(config('eventreel.route_name_prefix') . 'download', $latestVideo->id),
                'filename' => $latestVideo->video_filename ?: 'event-reel-' . now()->format('Y-m-d-His') . '.mp4'
            ]);
        }

        // Check if there are any queued or processing videos
        $processingVideos = ActivityLog::where('user_id', auth()->id())
            ->where('activity_type', 'event_reel_generation')
            ->whereIn('status', ['queued'])
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();

        if ($processingVideos) {
            return response()->json(['status' => 'processing']);
        }

        return response()->json(['status' => 'none']);
    }

    /**
     * Download completed video (called automatically by JavaScript).
     */
    public function download($activityLogId)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $activityLog = ActivityLog::where('id', $activityLogId)
            ->where('user_id', auth()->id())
            ->where('activity_type', 'event_reel_generation')
            ->where('status', 'completed')
            ->first();

        if (!$activityLog || !$activityLog->video_path) {
            \Log::error('Video not found for download', [
                'activity_log_id' => $activityLogId,
                'user_id' => auth()->id()
            ]);
            return response()->json(['error' => 'Video not found'], 404);
        }

        $disk = config('eventreel.storage.disk');

        if (!Storage::disk($disk)->exists($activityLog->video_path)) {
            \Log::error('Video file does not exist', [
                'activity_log_id' => $activityLogId,
                'path' => $activityLog->video_path,
                'disk' => $disk
            ]);
            return response()->json(['error' => 'Video file not available'], 404);
        }

        // Schedule cleanup after 30 seconds to allow download to complete
        CleanupDownloadedVideo::dispatch([
            'activity_log_id' => $activityLog->id,
            'disk' => $disk,
            'file_path' => $activityLog->video_path
        ])->delay(now()->addSeconds(30));


        // Clear the generation timestamp from session since download is starting
        session()->forget('video_generation_timestamp');

        return Storage::disk($disk)->download(
            $activityLog->video_path,
            $activityLog->video_filename ?: 'event-reel-' . now()->format('Y-m-d-His') . '.mp4'
        );
    }


    /**
     * Generate event reel from uploaded image or text.
     */
    public function generate(Request $request)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access the video generator.');
        }

        if (!auth()->user()->hasRole('member') && !auth()->user()->hasRole('admin')) {
            return redirect()->route('frontend.home')->with('error', 'Access denied. Member or admin access required.');
        }

        // Check subscription only if subscriptions are enabled
        if (config('app.enable_subscription', env('ENABLE_SUBSCRIPTION', false))) {
            // Skip subscription check for admins
            if (!auth()->user()->hasRole('admin') && !auth()->user()->hasActiveSubscription()) {
                return redirect()->route('member.subscriptions')->with('error', 'You need an active subscription to generate videos. Please subscribe to continue.');
            }
        }

        $request->validate([
            'flyer_image' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'event_text' => 'nullable|string|max:2000',
            'show_flyer' => 'nullable|boolean',
            'access_code' => 'nullable|string',
        ]);

        // Check access code if configured
        $requiredAccessCode = config('eventreel.access_code');
        if ($requiredAccessCode && $request->input('access_code') !== $requiredAccessCode) {
            return back()->withErrors(['access_code' => 'Invalid access code.'])->withInput();
        }

        try {
            $ocrService = app(OCRService::class);
            $eventText = $this->extractEventText($request, $ocrService);
            $showFlyer = $request->boolean('show_flyer', false);
            $flyerPath = null;

            if ($request->hasFile('flyer_image')) {
                $flyerPath = $this->storeFlyer($request->file('flyer_image'));
            }

            // Prepare data for the job
            $jobData = [
                'event_text' => $eventText,
                'show_flyer' => $showFlyer,
            ];

            // Store the generation timestamp in session to track this specific generation
            $generationTimestamp = now();
            session(['video_generation_timestamp' => $generationTimestamp]);

            // Dispatch the video generation job
            GenerateVideoReel::dispatch($jobData, auth()->id(), $flyerPath);

            // Log the queued job
            ActivityLog::create([
                'user_id' => auth()->id(),
                'activity_type' => 'event_reel_generation',
                'date' => now()->toDateString(),
                'workout_summary' => 'Queued event reel generation: ' . $eventText,
                'check_in_method' => 'web',
                'status' => 'queued'
            ]);

            // Return success response - stay on same page with processing message
            return back()->with('processing', 'Your video is being generated! Processing may take a few minutes. Please wait...')->withInput();

        } catch (\Exception $e) {
            // Clean up flyer if it was stored but job failed to queue
            if (isset($flyerPath) && $flyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($flyerPath);
            }

            \Log::error('Failed to queue video generation job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['error' => 'Failed to queue video generation: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Format content details into overlay text with line breaks.
     * Works with any content type (events, announcements, acknowledgements, etc.)
     */
    private function formatContentOverlay(array $details): string
    {
        // Extract lines in order (line1, line2, line3, line4, line5)
        $lines = [];
        for ($i = 1; $i <= 5; $i++) {
            $lineKey = "line{$i}";
            if (isset($details[$lineKey]) && !empty(trim($details[$lineKey]))) {
                $lines[] = trim($details[$lineKey]);
            }
        }
        
        // Filter out empty lines
        $lines = array_filter($lines, fn($line) => !empty(trim($line)));
        
        // Use actual newline character to separate lines
        return implode("\n", $lines);
    }

    private function extractEventText(Request $request, OCRService $ocrService): string
    {
        if ($request->filled('event_text')) {
            return $request->input('event_text');
        }

        if ($request->hasFile('flyer_image')) {
            $imagePath = $request->file('flyer_image')->getRealPath();
            return $ocrService->extractText($imagePath);
        }

        throw new \Exception('Either flyer image or event description must be provided.');
    }

    /**
     * Create optimal video search term using AI-generated keywords.
     */
    private function createOptimalVideoSearch(string $caption, array $videoKeywords): string
    {
        // If we have AI-generated keywords, use them as primary search terms
        if (!empty($videoKeywords)) {
            // Take top 3 AI keywords for best relevance
            $primaryKeywords = array_slice($videoKeywords, 0, 3);

            // Add some fallback keywords if AI keywords are too specific
            $searchTerms = $primaryKeywords;

            // If AI keywords don't include obvious terms, add contextual ones
            $hasContext = false;
            foreach ($videoKeywords as $keyword) {
                if (in_array(strtolower($keyword), ['birthday', 'wedding', 'celebration', 'party', 'event', 'corporate'])) {
                    $hasContext = true;
                    break;
                }
            }

            if (!$hasContext) {
                // Add contextual terms based on caption content
                if (stripos($caption, 'birthday') !== false) {
                    $searchTerms[] = 'birthday';
                } elseif (stripos($caption, 'wedding') !== false) {
                    $searchTerms[] = 'wedding';
                } else {
                    $searchTerms[] = 'celebration';
                }
            }

            return implode(' ', array_unique($searchTerms));
        }

        // Fallback to caption-based keyword extraction
        $words = explode(' ', strtolower($caption));
        $keywords = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array($word, ['this', 'that', 'with', 'from', 'your', 'will', 'have', 'been', 'were']);
        });

        return implode(' ', array_slice($keywords, 0, 3)) ?: 'celebration event';
    }

    /**
     * Store uploaded flyer image.
     */
    private function storeFlyer($file): string
    {
        $disk = config('eventreel.storage.disk');
        $path = config('eventreel.storage.temp_path') . '/' . Str::random(40) . '.' . $file->getClientOriginalExtension();

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

}

