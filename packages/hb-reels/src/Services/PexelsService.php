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
     */
    public function downloadVideo(string $caption): string
    {
        \Log::info('PexelsService: downloadVideo called', ['caption' => $caption]);

        if (empty($this->apiKey)) {
            \Log::error('PexelsService: API key not configured');
            throw new \Exception('Pexels API key is not configured. Please set PEXELS_API_KEY in your .env file.');
        }

        \Log::info('PexelsService: API key is configured', ['key_length' => strlen($this->apiKey)]);

        try {
            // Extract keywords from caption
            $keywords = $this->extractKeywords($caption);
            $searchQuery = implode(' ', array_slice($keywords, 0, 3)) ?: 'celebration event';

            \Log::info('PexelsService: Starting video search', [
                'caption' => $caption,
                'keywords' => $keywords,
                'search_query' => $searchQuery
            ]);

            // Search for videos with retry logic
            $maxRetries = config('eventreel.pexels.max_retries', 3);
            \Log::info('PexelsService: Calling searchVideo', ['max_retries' => $maxRetries]);
            $video = $this->searchVideo($searchQuery, $maxRetries);

            \Log::info('PexelsService: Video found', ['video_id' => $video['id'] ?? 'unknown']);

            // Find best quality portrait video
            $videoFile = $this->findBestVideoFile($video['video_files']);
            \Log::info('PexelsService: Best video file selected', ['quality' => $videoFile['quality'] ?? 'unknown']);

            // Download video with retry logic
            \Log::info('PexelsService: Starting video download');
            $result = $this->downloadVideoFile($videoFile['link'], $maxRetries);
            \Log::info('PexelsService: Video download completed successfully', ['result_path' => $result]);

            return $result;

        } catch (\Exception $e) {
            \Log::error('PexelsService: Primary video download failed', [
                'caption' => $caption,
                'error' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);

            // Try one more time with a very generic query (fewer retries for speed)
            try {
                \Log::info('PexelsService: Attempting fallback with generic query');
                $video = $this->searchVideo('celebration party event', 1, true);
                $videoFile = $this->findBestVideoFile($video['video_files']);
                return $this->downloadVideoFile($videoFile['link'], 1); // Only 1 retry for download
            } catch (\Exception $fallbackError) {
                \Log::error('PexelsService: Fallback search also failed', [
                    'error' => $fallbackError->getMessage(),
                    'error_class' => get_class($fallbackError)
                ]);
                // Last resort: try to use a cached/default video
                \Log::info('PexelsService: Trying fallback video');
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
     */
    private function searchVideo(string $query, int $maxRetries = 3, bool $isFallback = false): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                \Log::info('Pexels search attempt', [
                    'attempt' => $attempt,
                    'query' => $query,
                    'is_fallback' => $isFallback
                ]);

                $response = $this->client->get('/videos/search', [
                    'query' => [
                        'query' => $query,
                        'per_page' => 1,
                        'orientation' => 'portrait',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (!empty($data['videos'])) {
                    \Log::info('Pexels search successful', [
                        'query' => $query,
                        'video_count' => count($data['videos'])
                    ]);
                    return $data['videos'][0];
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
                    \Log::info('Waiting before retry', ['wait_time' => $waitTime]);
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
                \Log::info('Video download attempt', [
                    'attempt' => $attempt,
                    'url' => $url
                ]);

                $response = $this->client->get($url, [
                    'timeout' => config('eventreel.pexels.download_timeout', 60),
                    'connect_timeout' => config('eventreel.pexels.connect_timeout', 10),
                ]);

                $videoContent = $response->getBody()->getContents();
                Storage::disk($disk)->put($path, $videoContent);

                $fileSize = strlen($videoContent);
                \Log::info('Video download successful', [
                    'path' => $path,
                    'size' => $fileSize
                ]);

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
                    \Log::info('Waiting before download retry', ['wait_time' => $waitTime]);
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
                \Log::info('Using fallback video from public directory', ['path' => $defaultPath]);
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

