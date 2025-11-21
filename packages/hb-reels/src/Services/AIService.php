<?php

namespace HbReels\EventReelGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AIService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
        ]);
    }

    /**
     * Generate a polished caption from event text using Ollama.
     */
    public function generateCaption(string $eventText): string
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
        $model = config('eventreel.ollama_model', 'mistral');

        $prompt = "Rewrite the following event details into a polished 1-3 line short caption for a promotional video. Keep it engaging and concise:\n\n{$eventText}";

        try {
            $response = $this->client->post("{$ollamaUrl}/api/generate", [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return trim($data['response'] ?? $eventText);
        } catch (GuzzleException $e) {
            // Fallback to simple text processing if Ollama is not available
            return $this->fallbackCaption($eventText);
        }
    }

    /**
     * Fallback caption generation if Ollama is unavailable.
     */
    private function fallbackCaption(string $text): string
    {
        // Simple text cleaning and truncation
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Extract key information (date, time, location)
        $lines = explode("\n", $text);
        $keyLines = array_filter($lines, function($line) {
            $line = trim($line);
            return !empty($line) && strlen($line) > 5;
        });
        
        $caption = implode(' • ', array_slice($keyLines, 0, 3));
        
        return $caption ?: substr($text, 0, 100);
    }
}

