<?php

namespace HbReels\EventReelGenerator\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GrammarService
{
    private array $supportedLanguages = [
        'en' => 'English',
        'es' => 'Spanish', 
        'fr' => 'French',
        'de' => 'German',
        'hi' => 'Hindi',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'ml' => 'Malayalam',
        'kn' => 'Kannada',
        'bn' => 'Bengali',
        'gu' => 'Gujarati',
        'pa' => 'Punjabi',
        'or' => 'Oriya',
        'mr' => 'Marathi',
        'th' => 'Thai',
        'my' => 'Burmese',
        'km' => 'Khmer',
        'lo' => 'Lao',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ar' => 'Arabic',
        'fa' => 'Persian',
        'ur' => 'Urdu',
        'ru' => 'Russian',
        'uk' => 'Ukrainian'
    ];

    private Client $client;
    private bool $useGoogleAPI;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60, // Increased timeout for AI responses
            'connect_timeout' => 10
        ]);
        
        // Use Google Language API if configured, otherwise fallback to basic corrections
        $this->useGoogleAPI = config('eventreel.grammar_provider', 'ollama') === 'google';
    }

    /**
     * Check and correct grammar for the given text.
     *
     * @param string $text The text to check
     * @param string $language Language code (e.g., 'en', 'es', 'hi')
     * @return string Corrected text
     */
    public function checkGrammar(string $text, string $language = 'en'): string
    {
        try {
            // First try Ollama for grammar checking
            $corrected = $this->checkWithOllama($text, $language);
            
            if ($corrected && $corrected !== $text) {
                Log::info('Grammar corrected with Ollama', [
                    'original' => $text,
                    'corrected' => $corrected,
                    'language' => $language
                ]);
                return $corrected;
            }
            
            // Fallback to Google Language API if configured
            if ($this->useGoogleAPI) {
                $corrected = $this->checkWithGoogle($text, $language);
                if ($corrected && $corrected !== $text) {
                    Log::info('Grammar corrected with Google API', [
                        'original' => $text,
                        'corrected' => $corrected,
                        'language' => $language
                    ]);
                    return $corrected;
                }
            }
            
            // Final fallback to basic corrections
            $corrected = $this->basicCorrections($text, $language);
            Log::info('Applied basic grammar corrections', [
                'original' => $text,
                'corrected' => $corrected,
                'language' => $language
            ]);
            
            return $corrected;
            
        } catch (\Exception $e) {
            Log::error('Grammar check failed', [
                'error' => $e->getMessage(),
                'text' => $text,
                'language' => $language
            ]);
            
            // Return original text if all methods fail
            return $text;
        }
    }

    /**
     * Check grammar using Ollama AI.
     */
    private function checkWithOllama(string $text, string $language): ?string
    {
        try {
            $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
            $model = config('eventreel.ollama_grammar_model', 'phi'); // Use smaller model for grammar checking
            
            $languageName = $this->supportedLanguages[$language] ?? 'English';
            
            $prompt = "Please check and correct the grammar of this {$languageName} text. Only return the corrected text without any explanations or additional comments:\n\n\"{$text}\"";
            
            $response = $this->client->post($ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.1, // Low temperature for consistent corrections
                        'num_predict' => 200
                    ]
                ],
                'timeout' => 60
            ]);
            
            $result = json_decode($response->getBody(), true);
            $corrected = trim($result['response'] ?? '');
            
            // Remove quotes if they were added by the AI
            $corrected = preg_replace('/^["\']|["\']$/u', '', $corrected);
            
            return $corrected ?: null;
            
        } catch (\Exception $e) {
            Log::warning('Ollama grammar check failed', [
                'error' => $e->getMessage(),
                'language' => $language
            ]);
            return null;
        }
    }

    /**
     * Check grammar using Google Language API.
     */
    private function checkWithGoogle(string $text, string $language): ?string
    {
        // Note: Google Language API would require API key and proper implementation
        // For now, return null to use fallback
        Log::info('Google API grammar check not implemented yet');
        return null;
    }

    /**
     * Apply basic grammar corrections.
     */
    private function basicCorrections(string $text, string $language): string
    {
        $originalText = $text;

        // Basic corrections that work across languages
        
        // Fix multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Fix spaces before punctuation
        $text = preg_replace('/\s+([,.!?;:])/', '$1', $text);
        
        // Fix missing space after punctuation (basic)
        $text = preg_replace('/([,.!?;:])([A-Za-z])/u', '$1 $2', $text);
        
        // Fix capitalization for English
        if ($language === 'en') {
            // Capitalize first letter of sentences
            $text = preg_replace('/(?<=\.|\?|\!)\s*([a-z])/u', strtoupper('$1'), $text);
            // Capitalize first letter of text
            $text = preg_replace('/^[a-z]/u', strtoupper('$1'), $text);
        }
        
        // Fix common typos
        $text = str_replace(' i ', ' I ', $text); // English "i" to "I"

        // Fix common spelling errors (case-insensitive)
        $commonTypos = [
            'crismas' => 'Christmas',
            'crismus' => 'Christmas',
            'fro' => 'for',
            'recieve' => 'receive',
            'seperate' => 'separate',
            'occassion' => 'occasion',
            'begining' => 'beginning',
            'comming' => 'coming',
            'writting' => 'writing',
            'beleive' => 'believe',
            'wierd' => 'weird',
        ];

        foreach ($commonTypos as $wrong => $correct) {
            $text = str_ireplace($wrong, $correct, $text);
        }
        
        return trim($text);
    }

    /**
     * Get list of supported languages.
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Check if a language is supported.
     */
    public function isLanguageSupported(string $language): bool
    {
        return isset($this->supportedLanguages[$language]);
    }
}
