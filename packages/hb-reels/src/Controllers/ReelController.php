<?php

namespace HbReels\EventReelGenerator\Controllers;

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
        return view('eventreel::index');
    }

    /**
     * Generate event reel from uploaded image or text.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'flyer_image' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'event_text' => 'nullable|string|max:2000',
            'show_flyer' => 'nullable|boolean',
            'language' => 'nullable|string|in:auto,en,ta,hi,te,ml,kn,bn,gu,mr,pa,or,ar,fa,ur,th,my,km,lo,zh,ja,ko,ru,uk',
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
            $language = $request->input('language', 'auto');

            \Log::info('Language parameter received', [
                'language_from_request' => $request->input('language'),
                'language_defaulted' => $language,
                'all_request_data' => $request->all()
            ]);
            $flyerPath = null;

            if ($request->hasFile('flyer_image')) {
                $flyerPath = $this->storeFlyer($request->file('flyer_image'));
            }

            // Generate AI caption and video search optimization
            \Log::info('Starting AI caption generation', [
                'input_text' => substr($eventText, 0, 100),
                'selected_language' => $language
            ]);

            \Log::info('About to call generateCaption', [
                'event_text' => substr($eventText, 0, 100),
                'language_parameter' => $language,
                'language_type' => gettype($language)
            ]);

            $contentAnalysis = $aiService->generateCaption($eventText, $language);
            $caption = $contentAnalysis['caption'];
            $videoKeywords = $contentAnalysis['video_keywords'] ?? [];

            \Log::info('Caption generated', [
                'original_text' => substr($eventText, 0, 100),
                'generated_caption' => substr($caption, 0, 100),
                'language_used' => $language,
                'translation_occurred' => ($eventText !== $caption),
                'caption_contains_unicode' => preg_match('/[\x{0080}-\x{FFFF}]/u', $caption),
                'caption_contains_tamil' => preg_match('/[\x{0B80}-\x{0BFF}]/u', $caption)
            ]);

            \Log::info('AI Content Analysis Complete', [
                'caption' => $caption,
                'caption_language' => $language,
                'caption_length' => mb_strlen($caption),
                'video_keywords' => $videoKeywords,
                'content_type' => $contentAnalysis['content_analysis']['type'] ?? 'unknown',
                'tone' => $contentAnalysis['content_analysis']['tone'] ?? 'unknown'
            ]);

            // Extract structured details from text using AI (handles any content type)
            $contentDetails = $aiService->extractEventDetails($caption, $language);

            // Format overlay text from extracted details
            $overlayText = $this->formatContentOverlay($contentDetails);

            // Get stock video from Pexels using optimized keywords
            $videoSearchTerm = $this->createOptimalVideoSearch($caption, $videoKeywords);
            \Log::info('Starting Pexels video download (QUEUED)', [
                'caption' => $caption,
                'optimized_search' => $videoSearchTerm,
                'ai_keywords' => $videoKeywords
            ]);

            // Try queued video download first, fallback to synchronous if queue fails
            try {
                \Log::info('Attempting queued video download', [
                    'search_term' => $videoSearchTerm,
                    'language' => $language
                ]);

                // Dispatch video download job and wait for completion
                $jobId = $pexelsService->downloadVideoQueued($videoSearchTerm, [
                    'language' => $language,
                    'caption' => $caption,
                    'request_id' => $request->get('request_id', Str::random(16))
                ]);

                \Log::info('Video download job dispatched, waiting for completion', [
                    'job_id' => $jobId,
                    'search_term' => $videoSearchTerm
                ]);

                // Wait for the download to complete (with timeout)
                $stockVideoPath = $this->waitForVideoDownload($jobId, 60); // 60 second timeout

                \Log::info('✅ Queued video download successful', [
                    'job_id' => $jobId,
                    'video_path' => $stockVideoPath
                ]);

            } catch (\Exception $queueException) {
                \Log::warning('Queued video download failed, falling back to synchronous download', [
                    'error' => $queueException->getMessage(),
                    'search_term' => $videoSearchTerm
                ]);

                // Fallback to synchronous download
                $stockVideoPath = $pexelsService->downloadVideo($videoSearchTerm);

                \Log::info('✅ Synchronous video download successful (fallback)', [
                    'video_path' => $stockVideoPath,
                    'search_term' => $videoSearchTerm
                ]);
            }

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
            
            // Render final video with language support
            $outputPath = $videoRenderer->render(
                stockVideoPath: $stockVideoPath,
                flyerPath: $displayFlyerPath,
                caption: $displayCaption,
                language: $language
            );

            // Clean up temporary files
            if ($flyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($flyerPath);
            }
            if ($stockVideoPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($stockVideoPath);
            }

            // Debug: Log download attempt
            \Log::info('Video download initiated', [
                'output_path' => $outputPath,
                'language' => $language,
                'file_exists' => Storage::disk(config('eventreel.storage.disk'))->exists($outputPath),
                'file_size' => Storage::disk(config('eventreel.storage.disk'))->exists($outputPath) ?
                    Storage::disk(config('eventreel.storage.disk'))->size($outputPath) : 0
            ]);

            // Generate safe filename for download
            $safeFilename = 'event-reel-' . now()->format('Y-m-d-His') . '.mp4';

            // Debug: Log download filename encoding
            \Log::info('Download filename check', [
                'original_filename' => $safeFilename,
                'language' => $language,
                'filename_encoding' => mb_detect_encoding($safeFilename)
            ]);

            // Return download response
            return Storage::disk(config('eventreel.storage.disk'))->download(
                $outputPath,
                $safeFilename
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
     * Wait for a queued video download to complete.
     */
    private function waitForVideoDownload(string $jobId, int $timeoutSeconds = 60): string
    {
        $startTime = time();
        $pexelsService = app(PexelsService::class);

        \Log::info('Waiting for video download completion', [
            'job_id' => $jobId,
            'timeout' => $timeoutSeconds
        ]);

        while (time() - $startTime < $timeoutSeconds) {
            $status = $pexelsService->getDownloadStatus($jobId);

            \Log::info('Checking video download status', [
                'job_id' => $jobId,
                'status' => $status['status'] ?? 'unknown',
                'elapsed' => time() - $startTime
            ]);

            if ($status['status'] === 'completed') {
                \Log::info('Video download completed successfully', [
                    'job_id' => $jobId,
                    'video_path' => $status['video_path'],
                    'elapsed' => time() - $startTime
                ]);
                return $status['video_path'];
            }

            if ($status['status'] === 'failed' || $status['status'] === 'permanently_failed') {
                $error = $status['error'] ?? 'Unknown download error';
                \Log::error('Video download failed', [
                    'job_id' => $jobId,
                    'error' => $error,
                    'elapsed' => time() - $startTime
                ]);
                throw new \Exception("Video download failed: {$error}");
            }

            // Wait 2 seconds before checking again
            sleep(2);
        }

        \Log::error('Video download timeout', [
            'job_id' => $jobId,
            'timeout_seconds' => $timeoutSeconds,
            'elapsed' => time() - $startTime
        ]);

        throw new \Exception("Video download timed out after {$timeoutSeconds} seconds. Please try again.");
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

