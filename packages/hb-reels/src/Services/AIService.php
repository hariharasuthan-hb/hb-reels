<?php

namespace HbReels\EventReelGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class AIService
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 30,
        ]);
    }

    /**
     * Generate a polished caption from any text using Ollama.
     * Works with events, announcements, acknowledgements, or any content type.
     */
    public function generateCaption(string $text): string
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
        $model = config('eventreel.ollama_model', 'mistral');

        $prompt = "Rewrite the following text into a polished 1-3 line short caption for a promotional video. 
Keep it engaging and concise. The text could be an event, announcement, acknowledgement, or any message.
Maintain the original intent and key information.

Text:
{$text}

Caption:";

        try {
            $response = $this->client->post("{$ollamaUrl}/api/generate", [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return trim($data['response'] ?? $text);
        } catch (GuzzleException $e) {
            // Fallback to simple text processing if Ollama is not available
            return $this->fallbackCaption($text);
        }
    }

    /**
     * Extract structured details from any text using AI.
     * Intelligently handles events, announcements, acknowledgements, or any content type.
     * Returns array with keys: line1, line2, line3, line4, line5
     */
    public function extractEventDetails(string $text): array
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
        $model = config('eventreel.ollama_model', 'mistral');

        $prompt = "You are a content analyzer that extracts key information for video overlays.

Analyze the following text and identify its type (event, announcement, acknowledgement, promotion, etc.).
Then extract the most important information and format it into 3-5 short lines suitable for a video overlay.

IMPORTANT RULES:
1. Each line should be SHORT (max 50 characters)
2. Extract the most important information based on the content type
3. For EVENTS: Include title, date/time, location, highlights, call-to-action
4. For ANNOUNCEMENTS: Include main message, details, date (if any), call-to-action
5. For ACKNOWLEDGEMENTS: Include who is being acknowledged, reason, appreciation message
6. For GENERAL content: Extract key points in logical order
7. If information is missing, skip that line (don't use 'TBA')
8. Return ONLY a valid JSON object with numbered lines

Required JSON format (use these exact keys):
{
  \"line1\": \"First key information (title/main message)\",
  \"line2\": \"Second key information\",
  \"line3\": \"Third key information\",
  \"line4\": \"Fourth key information (optional)\",
  \"line5\": \"Fifth key information (optional, usually call-to-action)\"
}

Text to analyze:
{$text}

JSON:";

        try {
            $response = $this->client->post("{$ollamaUrl}/api/generate", [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'temperature' => 0.3, // Lower temperature for more consistent extraction
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $aiResponse = trim($data['response'] ?? '');
            
            \Log::info('AI Response for extraction:', ['response' => $aiResponse]);
            
            // Try to extract JSON from the response (handle both clean JSON and text with JSON)
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $aiResponse, $matches)) {
                $extracted = json_decode($matches[0], true);
                if ($extracted && is_array($extracted)) {
                    // Filter out empty lines
                    $lines = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $lineKey = "line{$i}";
                        if (isset($extracted[$lineKey]) && !empty(trim($extracted[$lineKey]))) {
                            $lines[$lineKey] = trim($extracted[$lineKey]);
                        }
                    }
                    
                    // If we got at least one line, return it
                    if (!empty($lines)) {
                        return $lines;
                    }
                }
            }
            
            // Fallback if JSON extraction fails
            \Log::warning('AI extraction failed, using fallback', ['response' => $aiResponse]);
            return $this->fallbackExtractDetails($text);
        } catch (GuzzleException $e) {
            \Log::error('AI service error', ['error' => $e->getMessage()]);
            return $this->fallbackExtractDetails($text);
        }
    }

    /**
     * Fallback content extraction using simple text parsing.
     * Intelligently splits text into 3-5 meaningful lines.
     */
    private function fallbackExtractDetails(string $text): array
    {
        // Clean up text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Split by sentences or newlines
        $sentences = preg_split('/[\.!\?]+|\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);
        $sentences = array_filter($sentences, fn($s) => strlen($s) > 3);
        
        // If we have very few sentences, try to split long ones
        if (count($sentences) < 3) {
            $newSentences = [];
            foreach ($sentences as $sentence) {
                if (strlen($sentence) > 80) {
                    // Split by commas or conjunctions
                    $parts = preg_split('/[,;]|\s+(?:and|or|but)\s+/', $sentence, -1, PREG_SPLIT_NO_EMPTY);
                    $newSentences = array_merge($newSentences, array_map('trim', $parts));
                } else {
                    $newSentences[] = $sentence;
                }
            }
            $sentences = $newSentences;
        }
        
        // Build lines array (up to 5 lines)
        $lines = [];
        $lineCount = min(5, count($sentences));
        
        for ($i = 0; $i < $lineCount; $i++) {
            if (isset($sentences[$i])) {
                // Truncate to 50 chars if needed
                $line = substr($sentences[$i], 0, 50);
                if (strlen($sentences[$i]) > 50) {
                    $line = substr($line, 0, 47) . '...';
                }
                $lines["line" . ($i + 1)] = $line;
            }
        }
        
        // Ensure we have at least one line
        if (empty($lines)) {
            $lines['line1'] = substr($text, 0, 50);
        }
        
        return $lines;
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

