<?php

namespace HbReels\EventReelGenerator\Controllers;

use App\Models\ActivityLog;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
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
        // Check if user is authenticated and has active subscription
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access the video generator.');
        }

        if (!auth()->user()->hasRole('member') && !auth()->user()->hasRole('admin')) {
            return redirect()->route('frontend.home')->with('error', 'Access denied. Member or admin access required.');
        }

        // Skip subscription check for admins
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasActiveSubscription()) {
            return redirect()->route('member.subscriptions')->with('error', 'You need an active subscription to generate videos. Please subscribe to continue.');
        }

        return view('eventreel::index');
    }

    /**
     * Generate event reel from uploaded image or text.
     */
    public function generate(Request $request)
    {
        // Check if user is authenticated and has active subscription
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access the video generator.');
        }

        if (!auth()->user()->hasRole('member') && !auth()->user()->hasRole('admin')) {
            return redirect()->route('frontend.home')->with('error', 'Access denied. Member or admin access required.');
        }

        // Skip subscription check for admins
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasActiveSubscription()) {
            return redirect()->route('member.subscriptions')->with('error', 'You need an active subscription to generate videos. Please subscribe to continue.');
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
            $aiService = app(AIService::class);
            $pexelsService = app(PexelsService::class);
            $videoRenderer = app(VideoRenderer::class);

            $eventText = $this->extractEventText($request, $ocrService);
            $showFlyer = $request->boolean('show_flyer', false);
            $flyerPath = null;

            if ($request->hasFile('flyer_image')) {
                $flyerPath = $this->storeFlyer($request->file('flyer_image'));
            }

            // Generate AI caption and video search optimization
            $contentAnalysis = $aiService->generateCaption($eventText);
            $caption = $contentAnalysis['caption'];
            $videoKeywords = $contentAnalysis['video_keywords'] ?? [];

            \Log::info('AI Content Analysis Complete', [
                'caption' => $caption,
                'video_keywords' => $videoKeywords,
                'content_type' => $contentAnalysis['content_analysis']['type'] ?? 'unknown',
                'tone' => $contentAnalysis['content_analysis']['tone'] ?? 'unknown'
            ]);

            // Extract structured details from text using AI (handles any content type)
            $contentDetails = $aiService->extractEventDetails($eventText);

            // Format overlay text from extracted details
            $overlayText = $this->formatContentOverlay($contentDetails);

            // Get stock video from Pexels using optimized keywords
            $videoSearchTerm = $this->createOptimalVideoSearch($caption, $videoKeywords);
            \Log::info('Starting Pexels video download', [
                'caption' => $caption,
                'optimized_search' => $videoSearchTerm,
                'ai_keywords' => $videoKeywords
            ]);
            $stockVideoPath = $pexelsService->downloadVideo($videoSearchTerm);

            // Determine what to show in the video:
            // - If showFlyer is TRUE: Show flyer only, no captions
            // - If showFlyer is FALSE and flyer exists: Show flyer + captions overlay
            // - If showFlyer is FALSE and no flyer: Show stock video + captions
            
            $displayFlyerPath = $flyerPath; // Always use flyer if it exists (background)
            $displayCaption = $showFlyer ? null : $overlayText; // Only hide caption if checkbox is checked
            
            \Log::info('Rendering video', [
                'showFlyer_checkbox' => $showFlyer,
                'flyerPath_exists' => $flyerPath ? 'yes' : 'no',
                'displayFlyerPath' => $displayFlyerPath ? 'yes' : 'no',
                'displayCaption' => $displayCaption,
            ]);
            
            // Render final video
            $outputPath = $videoRenderer->render(
                stockVideoPath: $stockVideoPath,
                flyerPath: $displayFlyerPath,
                caption: $displayCaption
            );

            // Log video generation activity
            $videoFilename = basename($outputPath);
            $videoSize = Storage::disk(config('eventreel.storage.disk'))->size($outputPath);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'activity_type' => 'event_reel_generation',
                'date' => now()->toDateString(),
                'workout_summary' => 'Generated event reel: ' . $eventText,
                'video_filename' => $videoFilename,
                'video_caption' => $overlayText,
                'video_path' => $outputPath,
                'video_size_bytes' => $videoSize,
                'check_in_method' => 'web',
            ]);

            // Clean up temporary files
            if ($flyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($flyerPath);
            }
            if ($stockVideoPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($stockVideoPath);
            }

            // Return download response
            return Storage::disk(config('eventreel.storage.disk'))->download(
                $outputPath,
                'event-reel-' . now()->format('Y-m-d-His') . '.mp4'
            );

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to generate reel: ' . $e->getMessage()])
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

