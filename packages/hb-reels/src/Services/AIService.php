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
     * Extract structured event details from event description using AI.
     * Returns array with keys: event_name, date_time, location, highlights, call_to_action
     */
    public function extractEventDetails(string $eventText): array
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
        $model = config('eventreel.ollama_model', 'mistral');

        $prompt = "You are an event information extractor. Extract the following details from the event description below and return ONLY a valid JSON object.

Required JSON format (use these exact keys):
{
  \"event_name\": \"the name or title of the event\",
  \"date_time\": \"the date and time information\",
  \"location\": \"the venue or location\",
  \"highlights\": \"key features, activities, or attractions\",
  \"call_to_action\": \"any RSVP, booking, or action message\"
}

Rules:
1. Extract actual information from the text below
2. If a detail is not found, write \"TBA\" for that field
3. Keep each field concise (max 50 characters)
4. Return ONLY the JSON object, no other text

Event description:
{$eventText}

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
                    return [
                        'event_name' => trim($extracted['event_name'] ?? 'Special Event'),
                        'date_time' => trim($extracted['date_time'] ?? 'TBA'),
                        'location' => trim($extracted['location'] ?? 'TBA'),
                        'highlights' => trim($extracted['highlights'] ?? 'Amazing experience'),
                        'call_to_action' => trim($extracted['call_to_action'] ?? 'Join us!'),
                    ];
                }
            }
            
            // Fallback if JSON extraction fails
            \Log::warning('AI extraction failed, using fallback', ['response' => $aiResponse]);
            return $this->fallbackExtractDetails($eventText);
        } catch (GuzzleException $e) {
            \Log::error('AI service error', ['error' => $e->getMessage()]);
            return $this->fallbackExtractDetails($eventText);
        }
    }

    /**
     * Fallback event details extraction using simple text parsing.
     */
    private function fallbackExtractDetails(string $text): array
    {
        // Split by common separators and extract key information
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Try to find event name (usually first sentence or line)
        $eventName = 'Special Event';
        if (preg_match('/^(.{5,60}?)[\.\!\?]/', $text, $matches)) {
            $eventName = trim($matches[1]);
        } elseif (preg_match('/^(.{5,60})/', $text, $matches)) {
            $eventName = trim($matches[1]);
        }
        
        // Try to find date/time patterns
        $dateTime = 'TBA';
        if (preg_match('/((?:Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Fri|Sat|Sun|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[^\.]*\d{1,2}[^\.]*(?:\d{1,2}:\d{2})?[^\.]*(?:AM|PM|am|pm)?)/i', $text, $matches)) {
            $dateTime = trim($matches[1]);
        } elseif (preg_match('/(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}[^\.]*(?:\d{1,2}:\d{2})?[^\.]*(?:AM|PM|am|pm)?)/i', $text, $matches)) {
            $dateTime = trim($matches[1]);
        }
        
        // Try to find location patterns
        $location = 'TBA';
        if (preg_match('/(?:at|@|location:|venue:)\s*([A-Z][^,\.\n]{5,50})/i', $text, $matches)) {
            $location = trim($matches[1]);
        }
        
        // Try to find highlights/activities
        $highlights = 'Don\'t miss out!';
        if (preg_match('/((?:enjoy|featuring|includes?|with|experience)[^\.]{10,80})/i', $text, $matches)) {
            $highlights = trim($matches[1]);
        }
        
        // Try to find call to action
        $cta = 'Join us!';
        if (preg_match('/((?:RSVP|register|book|reserve|sign up|get tickets)[^\.]{5,50})/i', $text, $matches)) {
            $cta = trim($matches[1]);
        }
        
        return [
            'event_name' => substr($eventName, 0, 50),
            'date_time' => substr($dateTime, 0, 50),
            'location' => substr($location, 0, 50),
            'highlights' => substr($highlights, 0, 80),
            'call_to_action' => substr($cta, 0, 50),
        ];
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

