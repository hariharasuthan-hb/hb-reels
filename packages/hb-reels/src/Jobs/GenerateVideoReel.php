<?php

namespace HbReels\EventReelGenerator\Jobs;

use App\Models\ActivityLog;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateVideoReel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60; // 1 minute delay between retries
    public $timeout = 600; // 10 minutes timeout

    protected array $data;
    protected int $userId;
    protected ?string $flyerPath;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, int $userId, ?string $flyerPath = null)
    {
        $this->data = $data;
        $this->userId = $userId;
        $this->flyerPath = $flyerPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting video reel generation job', [
                'user_id' => $this->userId,
                'job_id' => $this->job->getJobId()
            ]);

            $aiService = app(AIService::class);
            $grammarService = app(\HbReels\EventReelGenerator\Services\GrammarService::class);
            $pexelsService = app(PexelsService::class);
            $videoRenderer = app(VideoRenderer::class);

            $eventText = $this->data['event_text'];
            $showFlyer = $this->data['show_flyer'] ?? false;

            // AI-powered spell check and grammar correction
            $originalText = $eventText;
            $eventText = $grammarService->checkGrammar($eventText);

            Log::info('AI Grammar Check Applied', [
                'user_id' => $this->userId,
                'original_text' => $originalText,
                'corrected_text' => $eventText,
                'text_changed' => $originalText !== $eventText
            ]);

            // Generate AI caption and video search optimization
            $contentAnalysis = $aiService->generateCaption($eventText);
            $caption = $contentAnalysis['caption'];
            $videoKeywords = $contentAnalysis['video_keywords'] ?? [];

            Log::info('AI Content Analysis Complete', [
                'user_id' => $this->userId,
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
            Log::info('Starting Pexels video download', [
                'user_id' => $this->userId,
                'caption' => $caption,
                'optimized_search' => $videoSearchTerm,
                'ai_keywords' => $videoKeywords
            ]);
            $stockVideoPath = $pexelsService->downloadVideo($videoSearchTerm);

            // Determine what to show in the video:
            // - If showFlyer is TRUE: Show flyer only, no captions
            // - If showFlyer is FALSE and flyer exists: Show flyer + captions overlay
            // - If showFlyer is FALSE and no flyer: Show stock video + captions

            $displayFlyerPath = $this->flyerPath; // Always use flyer if it exists (background)
            $displayCaption = $showFlyer ? null : $overlayText; // Only hide caption if checkbox is checked

            Log::info('Rendering video', [
                'user_id' => $this->userId,
                'showFlyer_checkbox' => $showFlyer,
                'flyerPath_exists' => $this->flyerPath ? 'yes' : 'no',
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
                'user_id' => $this->userId,
                'activity_type' => 'event_reel_generation',
                'date' => now()->toDateString(),
                'workout_summary' => 'Generated event reel: ' . $eventText,
                'video_filename' => $videoFilename,
                'video_caption' => $overlayText,
                'video_path' => $outputPath,
                'video_size_bytes' => $videoSize,
                'check_in_method' => 'web',
                'job_id' => $this->job->getJobId(),
                'status' => 'completed'
            ]);

            // Clean up temporary files
            if ($this->flyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($this->flyerPath);
            }
            if ($stockVideoPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($stockVideoPath);
            }

            // Note: Cleanup is now handled by CleanupDownloadedVideo job when user downloads
            // Undownloaded videos will be cleaned up by scheduled cleanup jobs

            Log::info('Video generation completed successfully', [
                'user_id' => $this->userId,
                'output_path' => $outputPath,
                'video_size' => $videoSize,
                'job_id' => $this->job->getJobId()
            ]);

        } catch (\Exception $e) {
            Log::error('Video generation job failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log failed generation
            ActivityLog::create([
                'user_id' => $this->userId,
                'activity_type' => 'event_reel_generation',
                'date' => now()->toDateString(),
                'workout_summary' => 'Failed to generate event reel: ' . $this->data['event_text'],
                'check_in_method' => 'web',
                'job_id' => $this->job->getJobId(),
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            // Clean up flyer if it exists
            if ($this->flyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($this->flyerPath);
            }

            throw $e;
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
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Video reel generation job failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
            'attempts' => $this->attempts()
        ]);

        // Ensure failed status is logged
        ActivityLog::create([
            'user_id' => $this->userId,
            'activity_type' => 'event_reel_generation',
            'date' => now()->toDateString(),
            'workout_summary' => 'Failed to generate event reel: ' . ($this->data['event_text'] ?? 'Unknown'),
            'check_in_method' => 'web',
            'job_id' => $this->job->getJobId(),
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}