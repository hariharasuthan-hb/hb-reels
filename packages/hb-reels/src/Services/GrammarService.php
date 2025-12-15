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
            'timeout' => 120, // Increased timeout for AI responses
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
                return $corrected;
            }
            
            // Fallback to Google Language API if configured
            if ($this->useGoogleAPI) {
                $corrected = $this->checkWithGoogle($text, $language);
                if ($corrected && $corrected !== $text) {
                    return $corrected;
                }
            }
            
            // Final fallback to basic corrections
            $corrected = $this->basicCorrections($text, $language);
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
            $model = config('eventreel.ollama_model', 'mistral'); // Use Mistral model for grammar checking
            
            $languageName = $this->supportedLanguages[$language] ?? 'English';
            
            // More strict prompt to prevent explanations
            $prompt = "Correct the grammar of this {$languageName} text. Return ONLY the corrected text, nothing else. No explanations, no comments, no parentheses, no notes. Just the corrected text:\n\n{$text}";
            
            $response = $this->client->post($ollamaUrl . '/api/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'num_predict' => 100,   // limit output size for grammar corrections
                        'temperature' => 0.4,
                    ]
                ],
                'timeout' => 120
            ]);
            
            $result = json_decode($response->getBody(), true);
            $corrected = trim($result['response'] ?? '');
            
            // Remove quotes if they were added by the AI
            $corrected = preg_replace('/^["\']|["\']$/u', '', $corrected);
            
            // AGGRESSIVE CLEANUP: Remove all explanatory text patterns
            
            // Remove any text in parentheses (explanations are often in parentheses)
            $corrected = preg_replace('/\s*\([^)]*\)\s*/u', '', $corrected);
            
            // Remove any text in square brackets
            $corrected = preg_replace('/\s*\[[^\]]*\]\s*/u', '', $corrected);
            
            // Remove common explanation patterns (case-insensitive, with or without parentheses)
            $explanationPatterns = [
                '/\s*\(?\s*This\s+sentence\s+is\s+already\s+grammatically\s+correct\.?\s*\)?\s*/iu',
                '/\s*\(?\s*No\s+corrections?\s+needed\.?\s*\)?\s*/iu',
                '/\s*\(?\s*Already\s+correct\.?\s*\)?\s*/iu',
                '/\s*\(?\s*Grammatically\s+correct\.?\s*\)?\s*/iu',
                '/\s*\(?\s*No\s+changes?\s+needed\.?\s*\)?\s*/iu',
                '/\s*\(?\s*Correct\s+as\s+is\.?\s*\)?\s*/iu',
                '/\s*\(?\s*The\s+text\s+is\s+already\s+correct\.?\s*\)?\s*/iu',
                '/\s*\(?\s*No\s+grammar\s+errors\.?\s*\)?\s*/iu',
            ];
            
            foreach ($explanationPatterns as $pattern) {
                $corrected = preg_replace($pattern, '', $corrected);
            }
            
            // Split by sentences and filter out explanation-like sentences
            $sentences = preg_split('/[.!?]+/', $corrected, -1, PREG_SPLIT_NO_EMPTY);
            $validSentences = [];
            
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (empty($sentence)) {
                    continue;
                }
                
                // Skip sentences that are clearly explanations
                $lowerSentence = strtolower($sentence);
                if (preg_match('/^(this|the|it|that)\s+(sentence|text|phrase)\s+(is|are|was|were|has|have|already)/iu', $lowerSentence)) {
                    continue;
                }
                if (preg_match('/^(already|correct|grammatically|no\s+corrections?|no\s+changes?|the\s+text\s+is)/iu', $lowerSentence)) {
                    continue;
                }
                if (preg_match('/\b(already\s+correct|grammatically\s+correct|no\s+corrections?|no\s+changes?)\b/iu', $lowerSentence)) {
                    continue;
                }
                
                // If the sentence is the original text, keep it
                if (stripos($sentence, $text) !== false || stripos($text, $sentence) !== false) {
                    $validSentences[] = $sentence;
                    continue;
                }
                
                // Keep sentences that are reasonable length and don't look like explanations
                if (strlen($sentence) > 5 && strlen($sentence) < 200) {
                    $validSentences[] = $sentence;
                }
            }
            
            $corrected = implode('. ', $validSentences);
            $corrected = trim($corrected);
            
            // If the corrected text contains the original text, prefer the original
            // This handles cases where AI returns: "Original text. (Explanation)"
            if (stripos($corrected, $text) !== false) {
                // Extract just the part that matches the original
                $textStart = stripos($corrected, $text);
                if ($textStart === 0) {
                    // If it starts with original text, take just that part
                    $corrected = substr($corrected, 0, strlen($text));
                } else {
                    // If original is in the middle, try to extract it
                    $before = substr($corrected, 0, $textStart);
                    $after = substr($corrected, $textStart + strlen($text));
                    // If what's before looks like explanation, use just original
                    if (preg_match('/explanation|note|comment|correct|grammar/iu', $before)) {
                        $corrected = $text;
                    }
                }
            }
            
            // Final cleanup: remove multiple spaces
            $corrected = preg_replace('/\s+/', ' ', $corrected);
            $corrected = trim($corrected);
            
            // If after all cleaning we have nothing meaningful, return null to use original
            if (empty($corrected) || 
                strlen($corrected) < strlen($text) * 0.3 || 
                $corrected === $text) {
                // If it's the same as original or too short, return null (will use original)
                return null;
            }
            
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
