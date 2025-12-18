<?php

namespace HbReels\EventReelGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PexelsService
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.pexels.com',
            'headers' => [
                'Authorization' => config('eventreel.pexels_api_key', ''),
            ],
            'timeout' => config('eventreel.pexels.timeout', 45),
            'connect_timeout' => config('eventreel.pexels.connect_timeout', 10),
        ]);

        $this->apiKey = config('eventreel.pexels_api_key', '');
    }

    /**
     * Download a relevant stock video from Pexels based on caption keywords.
     * 
     * @param string $caption The caption or search query
     * @param int $page Optional page number for getting different videos (for multiple captions)
     * @return string Path to downloaded video
     */
    public function downloadVideo(string $caption, int $page = 1): string
    {
        if (empty($this->apiKey)) {
            \Log::error('PexelsService: API key not configured');
            throw new \Exception('Pexels API key is not configured. Please set PEXELS_API_KEY in your .env file.');
        }

        try {
            // Use the caption directly if it contains good keywords, otherwise extract
            // This ensures AI-generated captions with specific keywords are used properly
            $captionLower = strtolower($caption);
            $hasSpecificKeywords = preg_match('/\b(birthday|wedding|corporate|celebration|party|event|product|launch|anniversary|graduation|festival|gathering|meeting|conference)\b/i', $caption);
            
            if ($hasSpecificKeywords && strlen($caption) < 100) {
                // Use caption directly if it's short and has relevant keywords
                $searchQuery = $caption;
            } else {
                // Extract keywords from caption for longer text
                $keywords = $this->extractKeywords($caption);
                $searchQuery = implode(' ', array_slice($keywords, 0, 3)) ?: 'celebration event';
            }

            // Search for videos with retry logic
            $maxRetries = config('eventreel.pexels.max_retries', 3);
            $video = $this->searchVideo($searchQuery, $maxRetries, false, $page);

            // Find best quality portrait video
            $videoFile = $this->findBestVideoFile($video['video_files']);

            // Download video with retry logic
            $result = $this->downloadVideoFile($videoFile['link'], $maxRetries);

            return $result;

        } catch (\Exception $e) {
            \Log::error('PexelsService: Primary video download failed', [
                'caption' => $caption,
                'error' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);

            // Try one more time with a very generic query (fewer retries for speed)
            try {
                $video = $this->searchVideo('celebration party event', 1, true);
                $videoFile = $this->findBestVideoFile($video['video_files']);
                return $this->downloadVideoFile($videoFile['link'], 1); // Only 1 retry for download
            } catch (\Exception $fallbackError) {
                \Log::error('PexelsService: Fallback search also failed', [
                    'error' => $fallbackError->getMessage(),
                    'error_class' => get_class($fallbackError)
                ]);
                // Last resort: try to use a cached/default video
                return $this->getFallbackVideo();
            }
        }
    }

    /**
     * Find the best quality portrait video file.
     */
    private function findBestVideoFile(array $videoFiles): array
    {
        // Prefer HD quality, portrait orientation
        $preferredQualities = ['hd', 'sd'];
        
        foreach ($preferredQualities as $quality) {
            foreach ($videoFiles as $file) {
                if (isset($file['quality']) && $file['quality'] === $quality) {
                    return $file;
                }
            }
        }

        // Fallback to first available
        return $videoFiles[0];
    }

    /**
     * Extract keywords from caption.
     */
    private function extractKeywords(string $text): array
    {
        // Remove common words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being'];
        
        $words = str_word_count(strtolower($text), 1);
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 3;
        });

        return array_values($keywords);
    }

    /**
     * Search for videos on Pexels with retry logic.
     * 
     * @param string $query Search query
     * @param int $maxRetries Maximum retry attempts
     * @param bool $isFallback Whether this is a fallback search
     * @param int $page Page number for pagination (to get different videos)
     * @return array Video data
     */
    private function searchVideo(string $query, int $maxRetries = 3, bool $isFallback = false, int $page = 1): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $this->client->get('/videos/search', [
                    'query' => [
                        'query' => $query,
                        'per_page' => min(10, max(1, $page)), // Get more videos if page > 1
                        'orientation' => 'portrait',
                        'page' => $page,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (!empty($data['videos'])) {
                    // Add randomness to video selection to prevent repetition
                    // Use page number + random offset to select different videos
                    $randomOffset = rand(0, min(2, count($data['videos']) - 1)); // Random offset 0-2
                    $videoIndex = (($page - 1) + $randomOffset) % count($data['videos']);
                    
                    \Log::info('Video selection with randomness', [
                        'query' => $query,
                        'page' => $page,
                        'total_videos' => count($data['videos']),
                        'selected_index' => $videoIndex,
                        'random_offset' => $randomOffset
                    ]);
                    
                    return $data['videos'][$videoIndex];
                }

                // If no videos found and not already a fallback, try generic search
                if (!$isFallback) {
                    \Log::warning('No videos found for query, trying fallback', ['query' => $query]);
                    return $this->searchVideo('celebration event party', $maxRetries, true);
                }

                // If even fallback fails, throw exception
                throw new \Exception('No suitable videos found on Pexels');

            } catch (GuzzleException $e) {
                $lastException = $e;
                \Log::warning('Pexels search attempt failed', [
                    'attempt' => $attempt,
                    'query' => $query,
                    'error' => $e->getMessage()
                ]);

                // If this is not the last attempt, wait before retrying
                if ($attempt < $maxRetries) {
                    $waitTime = $attempt * 2; // Exponential backoff: 2s, 4s, 6s
                    sleep($waitTime);
                }
            }
        }

        // All attempts failed
        throw new \Exception('Failed to search Pexels after ' . $maxRetries . ' attempts: ' . $lastException->getMessage());
    }

    /**
     * Download video file from URL with retry logic.
     */
    private function downloadVideoFile(string $url, int $maxRetries = 3): string
    {
        $disk = config('eventreel.storage.disk');
        $path = config('eventreel.storage.temp_path') . '/' . Str::random(40) . '.mp4';
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $this->client->get($url, [
                    'timeout' => config('eventreel.pexels.download_timeout', 60),
                    'connect_timeout' => config('eventreel.pexels.connect_timeout', 10),
                ]);

                $videoContent = $response->getBody()->getContents();
                Storage::disk($disk)->put($path, $videoContent);

                return $path;

            } catch (GuzzleException $e) {
                $lastException = $e;
                \Log::warning('Video download attempt failed', [
                    'attempt' => $attempt,
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);

                // If this is not the last attempt, wait before retrying
                if ($attempt < $maxRetries) {
                    $waitTime = $attempt * 3; // Longer wait for downloads: 3s, 6s, 9s
                    sleep($waitTime);
                }
            }
        }

        // All attempts failed
        throw new \Exception('Failed to download video after ' . $maxRetries . ' attempts: ' . $lastException->getMessage());
    }

    /**
     * Get a fallback video when Pexels is completely unavailable.
     * This copies a default video from the public directory or creates a placeholder.
     */
    private function getFallbackVideo(): string
    {
        $disk = config('eventreel.storage.disk');
        $fallbackPath = config('eventreel.storage.temp_path') . '/fallback-' . Str::random(20) . '.mp4';

        // Try to copy a default video from public directory
        $defaultVideoPaths = [
            public_path('videos/default-celebration.mp4'),
            public_path('videos/fallback.mp4'),
            public_path('default-video.mp4'),
        ];

        foreach ($defaultVideoPaths as $defaultPath) {
            if (file_exists($defaultPath)) {
                $content = file_get_contents($defaultPath);
                Storage::disk($disk)->put($fallbackPath, $content);
                return $fallbackPath;
            }
        }

        // If no default video exists, create a simple placeholder message
        // For now, we'll throw an exception with instructions
        throw new \Exception(
            'Pexels API is unavailable and no fallback video found. ' .
            'Please check your internet connection, PEXELS_API_KEY, or add a default video to public/videos/fallback.mp4'
        );
    }
}

