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
            $aiService = app(AIService::class);
            $grammarService = app(\HbReels\EventReelGenerator\Services\GrammarService::class);
            $pexelsService = app(PexelsService::class);
            $videoRenderer = app(VideoRenderer::class);

            $eventText = $this->data['event_text'];
            $showFlyer = $this->data['show_flyer'] ?? false;

            // Remove emojis and icons from the description before processing
            $eventText = $this->removeEmojisAndIcons($eventText);

            // AI-powered spell check and grammar correction
            $originalText = $eventText;
            $eventText = $grammarService->checkGrammar($eventText);

            // Generate AI caption and video search optimization
            $contentAnalysis = $aiService->generateCaption($eventText);
            $caption = $contentAnalysis['caption'];
            $videoKeywords = $contentAnalysis['video_keywords'] ?? [];

            // Extract structured details from text using AI (handles any content type)
            $contentDetails = $aiService->extractEventDetails($eventText);

            // Format overlay text from extracted details
            $overlayText = $this->formatContentOverlay($contentDetails);

            // Split caption into lines to determine if we need multiple videos
            $captionLines = preg_split('/\r\n|\r|\n/', $overlayText, -1, PREG_SPLIT_NO_EMPTY);
            $captionLines = array_filter($captionLines, fn($line) => !empty(trim($line)));
            $captionLineCount = count($captionLines);

            // Check if multiple videos feature is enabled
            $enableMultipleVideos = config('eventreel.video.enable_multiple_videos', false);

            // If multiple videos is enabled, generate exactly 3 videos with COMMON FULL CAPTION on all three
            // Each video will use different Pexels video for variety, but all show the same full caption
            if ($enableMultipleVideos) {
                $videoSegments = [];
                $tempVideoPaths = [];

                // Always generate exactly 3 videos when multiple videos is enabled
                $numberOfVideos = 3;
                
                // Generate exactly 3 videos, each with different Pexels video but SAME full caption
                for ($i = 0; $i < $numberOfVideos; $i++) {
                    $lineNumber = $i + 1;
                    
                    // For video search variety: use different caption lines if available, otherwise use full caption
                    // This helps get different videos from Pexels, but the displayed caption will be the same for all
                    if ($captionLineCount > 0 && isset($captionLines[$i])) {
                        $captionLineForSearch = $captionLines[$i];
                    } elseif ($captionLineCount > 0) {
                        // Cycle through available lines if we have fewer than 3
                        $captionLineForSearch = $captionLines[$i % $captionLineCount];
                    } else {
                        // Use full caption for search if no lines available
                        $captionLineForSearch = $overlayText;
                    }

                    // Create search term for video variety (different videos from Pexels)
                    $lineSearchTerm = $this->createOptimalVideoSearch($captionLineForSearch, $videoKeywords);
                    
                    // Use different page number to get different videos from Pexels
                    $stockVideoPath = $pexelsService->downloadVideo($lineSearchTerm, $lineNumber);
                    $tempVideoPaths[] = $stockVideoPath;

                    // Determine what to show for this segment
                    $displayFlyerPath = $this->flyerPath;
                    // IMPORTANT: Use COMMON FULL CAPTION for all three videos (not individual lines)
                    $displayCaption = $showFlyer ? null : $overlayText; // Same full caption on all three videos

                    // Render video segment with common full caption
                    $segmentPath = $videoRenderer->render(
                        stockVideoPath: $stockVideoPath,
                        flyerPath: $displayFlyerPath,
                        caption: $displayCaption
                    );

                    $videoSegments[] = $segmentPath;
                }

                // Concatenate all video segments into one final video
                if (count($videoSegments) !== 3) {
                    Log::error('Multiple video generation failed: Expected 3 segments but got ' . count($videoSegments), [
                        'user_id' => $this->userId,
                        'segments_count' => count($videoSegments)
                    ]);
                }
                
                $outputPath = $this->concatenateVideos($videoSegments, $videoRenderer);
                
                // Clean up segment files
                foreach ($videoSegments as $segmentPath) {
                    Storage::disk(config('eventreel.storage.disk'))->delete($segmentPath);
                }
                foreach ($tempVideoPaths as $tempPath) {
                    Storage::disk(config('eventreel.storage.disk'))->delete($tempPath);
                }

            } else {
                // Single video mode: Use one video for all captions
                // This applies when:
                // - Only one caption line exists, OR
                // - Multiple videos feature is disabled (ENABLE_MULTIPLE_VIDEO=false)
                $videoSearchTerm = $this->createOptimalVideoSearch($caption, $videoKeywords);
                $stockVideoPath = $pexelsService->downloadVideo($videoSearchTerm);

                // Determine what to show in the video:
                // - If showFlyer is TRUE: Show flyer only, no captions
                // - If showFlyer is FALSE and flyer exists: Show flyer + captions overlay
                // - If showFlyer is FALSE and no flyer: Show stock video + captions

                $displayFlyerPath = $this->flyerPath; // Always use flyer if it exists (background)
                $displayCaption = $showFlyer ? null : $overlayText; // Only hide caption if checkbox is checked

                // Render final video
                $outputPath = $videoRenderer->render(
                    stockVideoPath: $stockVideoPath,
                    flyerPath: $displayFlyerPath,
                    caption: $displayCaption
                );

                // Clean up temp video
                if ($stockVideoPath) {
                    Storage::disk(config('eventreel.storage.disk'))->delete($stockVideoPath);
                }
            }

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

            // Note: Cleanup is now handled by CleanupDownloadedVideo job when user downloads
            // Undownloaded videos will be cleaned up by scheduled cleanup jobs

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
     * Concatenate multiple video segments into one final video.
     */
    private function concatenateVideos(array $videoPaths, VideoRenderer $videoRenderer): string
    {
        $disk = config('eventreel.storage.disk');
        $ffmpegPath = config('eventreel.ffmpeg.path', 'ffmpeg');
        $outputPath = config('eventreel.storage.output_path') . '/' . Str::random(40) . '.mp4';
        $outputFullPath = Storage::disk($disk)->path($outputPath);

        // Ensure output directory exists
        $outputDir = dirname($outputFullPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Create file list for FFmpeg concat
        $fileListPath = storage_path('app/temp/concat_' . Str::random(20) . '.txt');
        $fileListDir = dirname($fileListPath);
        if (!is_dir($fileListDir)) {
            mkdir($fileListDir, 0755, true);
        }

        $fileListContent = '';
        foreach ($videoPaths as $videoPath) {
            $fullVideoPath = Storage::disk($disk)->path($videoPath);
            // Escape single quotes and backslashes for FFmpeg concat format
            $escapedPath = str_replace(['\'', '\\'], ['\\\'', '\\\\'], $fullVideoPath);
            $fileListContent .= "file '{$escapedPath}'\n";
        }

        file_put_contents($fileListPath, $fileListContent);

        // Build FFmpeg concat command
        $command = sprintf(
            '%s -f concat -safe 0 -i %s -c copy %s',
            escapeshellarg($ffmpegPath),
            escapeshellarg($fileListPath),
            escapeshellarg($outputFullPath)
        );


        // Execute FFmpeg
        exec($command . ' 2>&1', $output, $returnCode);

        // Clean up file list
        @unlink($fileListPath);

        if ($returnCode !== 0) {
            Log::error('Video concatenation failed', [
                'user_id' => $this->userId,
                'error' => implode("\n", $output),
                'return_code' => $returnCode
            ]);
            throw new \Exception('Video concatenation failed: ' . implode("\n", $output));
        }


        return $outputPath;
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
     * Remove emojis and icons from text.
     * This prevents errors in AI processing and FFmpeg rendering.
     */
    private function removeEmojisAndIcons(string $text): string
    {
        // Remove emojis (Unicode emoji ranges)
        // This covers most emoji ranges including:
        // - Emoticons (😀-🙏)
        // - Symbols & Pictographs (🌀-🗿)
        // - Transport & Map Symbols (🚀-🛿)
        // - Supplemental Symbols (🔼-🆎)
        // - Symbols & Pictographs Extended-A (🰀-🿿)
        // - And more
        $text = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $text); // Miscellaneous Symbols and Pictographs
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text); // Emoticons
        $text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $text); // Miscellaneous Symbols and Pictographs
        $text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $text); // Transport and Map Symbols
        $text = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', '', $text); // Flags
        $text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $text); // Miscellaneous Symbols
        $text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $text); // Dingbats
        $text = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $text); // Variation Selectors
        $text = preg_replace('/[\x{200D}]/u', '', $text); // Zero Width Joiner
        $text = preg_replace('/[\x{1FA00}-\x{1FAFF}]/u', '', $text); // Symbols and Pictographs Extended-A
        
        // Remove common icon/symbol characters
        $text = preg_replace('/[\x{2190}-\x{21FF}]/u', '', $text); // Arrows
        $text = preg_replace('/[\x{2300}-\x{23FF}]/u', '', $text); // Miscellaneous Technical
        $text = preg_replace('/[\x{2B00}-\x{2BFF}]/u', '', $text); // Miscellaneous Symbols and Arrows
        $text = preg_replace('/[\x{25A0}-\x{25FF}]/u', '', $text); // Geometric Shapes
        
        // Remove other common special characters that might be used as icons
        $text = str_replace(['★', '☆', '♥', '♦', '♣', '♠', '•', '○', '●', '■', '□', '▲', '△', '▼', '▽'], '', $text);
        $text = str_replace(['→', '←', '↑', '↓', '↔', '⇒', '⇐', '⇑', '⇓'], '', $text);
        $text = str_replace(['✓', '✗', '✘', '✕', '✖', '✗', '✚', '✛', '✜', '✝', '✞', '✟'], '', $text);
        $text = str_replace(['©', '®', '™', '℠', '℗'], '', $text);
        
        // Clean up multiple spaces and trim
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
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