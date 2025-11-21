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
            'timeout' => 30,
        ]);
        
        $this->apiKey = config('eventreel.pexels_api_key', '');
    }

    /**
     * Download a relevant stock video from Pexels based on caption keywords.
     */
    public function downloadVideo(string $caption): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Pexels API key is not configured.');
        }

        // Extract keywords from caption
        $keywords = $this->extractKeywords($caption);
        $searchQuery = implode(' ', array_slice($keywords, 0, 3)) ?: 'celebration event';

        // Search for videos
        $video = $this->searchVideo($searchQuery);

        // Find best quality portrait video
        $videoFile = $this->findBestVideoFile($video['video_files']);

        // Download video
        return $this->downloadVideoFile($videoFile['link']);
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
     * Search for videos on Pexels.
     */
    private function searchVideo(string $query): array
    {
        try {
            $response = $this->client->get('/videos/search', [
                'query' => [
                    'query' => $query,
                    'per_page' => 1,
                    'orientation' => 'portrait',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['videos'])) {
                // Fallback to generic event/celebration video
                return $this->searchVideo('celebration event party');
            }

            return $data['videos'][0];
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to search Pexels: ' . $e->getMessage());
        }
    }

    /**
     * Download video file from URL.
     */
    private function downloadVideoFile(string $url): string
    {
        $disk = config('eventreel.storage.disk');
        $path = config('eventreel.storage.temp_path') . '/' . Str::random(40) . '.mp4';

        try {
            $videoContent = file_get_contents($url);
            Storage::disk($disk)->put($path, $videoContent);

            return $path;
        } catch (\Exception $e) {
            throw new \Exception('Failed to download video: ' . $e->getMessage());
        }
    }
}

