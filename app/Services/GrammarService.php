<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class GrammarService
{
    private ClientInterface $client;
    private string $provider;
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
        'uk' => 'Ukrainian',
    ];

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client(['timeout' => 30]);
        $this->provider = config('eventreel.grammar_provider', 'auto');
    }

    /**
     * Check and correct grammar for any supported language.
     *
     * @param string $text Text to check
     * @param string $language Language code (e.g., 'en', 'ta', 'hi')
     * @return string Corrected text
     */
    public function checkGrammar(string $text, string $language = 'en'): string
    {
        if (!$this->isEnabled()) {
            Log::info('Grammar checking disabled, returning original text');
            return $text;
        }

        if (empty(trim($text))) {
            return $text;
        }

        Log::info('Starting grammar check', [
            'language' => $language,
            'text_length' => strlen($text),
            'provider' => $this->provider
        ]);

        // Try providers in order of preference
        $providers = $this->getProvidersToTry();

        foreach ($providers as $provider) {
            try {
                Log::info("Attempting grammar check with provider: {$provider}");

                $corrected = $this->checkWithProvider($text, $language, $provider);

                if ($corrected !== false && $corrected !== $text) {
                    Log::info('Grammar check successful', [
                        'provider' => $provider,
                        'original_length' => strlen($text),
                        'corrected_length' => strlen($corrected)
                    ]);
                    return $corrected;
                }

            } catch (\Exception $e) {
                Log::warning("Grammar check failed with provider {$provider}", [
                    'error' => $e->getMessage(),
                    'language' => $language
                ]);
            }
        }

        // Fallback to basic corrections
        Log::info('All grammar providers failed, using basic corrections');
        return $this->basicCorrections($text, $language);
    }

    /**
     * Check if grammar checking is enabled
     */
    private function isEnabled(): bool
    {
        return config('eventreel.grammar_enabled', false);
    }

    /**
     * Get list of providers to try in order
     */
    private function getProvidersToTry(): array
    {
        if ($this->provider === 'auto') {
            return ['ollama', 'google'];
        }

        return [$this->provider];
    }

    /**
     * Check grammar using specified provider
     */
    private function checkWithProvider(string $text, string $language, string $provider): string|false
    {
        return match ($provider) {
            'ollama' => $this->checkWithOllama($text, $language),
            'google' => $this->checkWithGoogle($text, $language),
            default => false
        };
    }

    /**
     * Check grammar using Ollama (local AI)
     */
    private function checkWithOllama(string $text, string $language, string $model = 'mistral'): string|false
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');

        // Get language-specific instructions
        $languageInstructions = $this->getLanguageInstructions($language);

        $prompt = "You are a professional editor specializing in {$languageInstructions['name']} language.

TASK: Correct grammar, punctuation, and improve clarity in the following {$languageInstructions['name']} text.

INSTRUCTIONS:
- Fix any grammatical errors
- Correct punctuation
- Improve sentence structure
- Maintain the original meaning
- Keep the text natural and fluent
- Return ONLY the corrected text, no explanations

IMPORTANT: For {$languageInstructions['name']}, pay special attention to:
{$languageInstructions['rules']}

Text to correct:
{$text}

Corrected text:";

        try {
            $response = $this->client->post("{$ollamaUrl}/api/generate", [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'temperature' => 0.1, // Low temperature for consistency
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $corrected = trim($data['response'] ?? '');

            return !empty($corrected) ? $corrected : false;

        } catch (GuzzleException $e) {
            Log::warning('Ollama grammar check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check grammar using Google Language API
     */
    private function checkWithGoogle(string $text, string $language): string|false
    {
        $apiKey = config('services.google.api_key');

        if (empty($apiKey)) {
            Log::warning('Google API key not configured for grammar checking');
            return false;
        }

        try {
            // Use Google's Natural Language API for grammar checking
            $response = $this->client->post("https://language.googleapis.com/v1/documents:analyzeSyntax?key={$apiKey}", [
                'json' => [
                    'document' => [
                        'content' => $text,
                        'type' => 'PLAIN_TEXT',
                        'language' => $language
                    ],
                    'encodingType' => 'UTF8'
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Process Google NLP response to identify and correct grammar issues
            return $this->processGoogleNLPResponse($text, $data, $language);

        } catch (GuzzleException $e) {
            Log::warning('Google grammar check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process Google NLP response for grammar corrections
     */
    private function processGoogleNLPResponse(string $originalText, array $nlpData, string $language): string
    {
        // This is a simplified implementation
        // In a full implementation, you would analyze tokens, dependencies, and part-of-speech
        // to identify grammar issues and suggest corrections

        $tokens = $nlpData['tokens'] ?? [];

        // For now, return original text if no obvious issues found
        // A full implementation would require more complex NLP processing
        return $originalText;
    }

    /**
     * Get language-specific grammar instructions
     */
    private function getLanguageInstructions(string $language): array
    {
        $instructions = [
            'en' => [
                'name' => 'English',
                'rules' => '- Subject-verb agreement
- Proper tense usage
- Correct article usage (a/an/the)
- Punctuation rules
- Sentence structure'
            ],
            'es' => [
                'name' => 'Spanish',
                'rules' => '- Gender agreement (masculine/feminine)
- Verb conjugations and tenses
- Proper use of subjunctive mood
- Accent marks on stressed syllables
- Ser vs Estar usage'
            ],
            'fr' => [
                'name' => 'French',
                'rules' => '- Gender agreement (masculine/feminine/plural)
- Verb conjugations with correct endings
- Proper use of subjunctive mood
- Liaison rules between words
- Accent marks and cedillas'
            ],
            'de' => [
                'name' => 'German',
                'rules' => '- Gender agreement (der/die/das)
- Case system (nominative/accusative/dative/genitive)
- Verb conjugations and word order
- Proper use of separable verbs
- Capitalization of nouns'
            ],
            'hi' => [
                'name' => 'Hindi',
                'rules' => '- Gender agreement (masculine/feminine)
- Verb forms and conjugations
- Postpositions (का/की/के/को etc.)
- Honorifics and respect forms
- Word gender and number agreement'
            ],
            'ta' => [
                'name' => 'Tamil',
                'rules' => '- Subject-object-verb word order
- Proper use of case markers (ஐ/ஐய/உடைய etc.)
- Verb conjugations and tense markers
- Honorific forms (த/து/த்)
- Sandhi rules between words'
            ],
            'te' => [
                'name' => 'Telugu',
                'rules' => '- Verb conjugations and tense forms
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels
- Compound word formation'
            ],
            'ml' => [
                'name' => 'Malayalam',
                'rules' => '- Subject-object-verb word order
- Verb conjugations and tense markers
- Proper use of case markers
- Honorific forms and respect levels
- Sandhi rules between words'
            ],
            'kn' => [
                'name' => 'Kannada',
                'rules' => '- Subject-object-verb word order
- Verb conjugations and tense forms
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels'
            ],
            'bn' => [
                'name' => 'Bengali',
                'rules' => '- Subject-object-verb word order
- Verb conjugations and compound verbs
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels'
            ],
            'gu' => [
                'name' => 'Gujarati',
                'rules' => '- Verb conjugations and tense forms
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels
- Compound word formation'
            ],
            'pa' => [
                'name' => 'Punjabi',
                'rules' => '- Verb conjugations and tense forms
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels
- Word gender and number agreement'
            ],
            'or' => [
                'name' => 'Oriya',
                'rules' => '- Verb conjugations and tense forms
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels
- Compound word formation'
            ],
            'mr' => [
                'name' => 'Marathi',
                'rules' => '- Verb conjugations and tense forms
- Gender agreement (masculine/feminine/neuter)
- Proper use of postpositions
- Honorific forms and respect levels
- Compound word formation'
            ],
            'th' => [
                'name' => 'Thai',
                'rules' => '- No spaces between words in written form
- Proper use of particles (ครับ/ค่ะ)
- Honorific forms and respect levels
- Word order (subject-verb-object)
- Tone markers (if applicable)'
            ],
            'my' => [
                'name' => 'Burmese',
                'rules' => '- Verb conjugations and tense forms
- Honorific forms and respect levels
- Proper use of particles
- Word order and sentence structure
- Politeness levels in language'
            ],
            'km' => [
                'name' => 'Khmer',
                'rules' => '- Verb conjugations and tense forms
- Honorific forms and respect levels
- Proper use of particles
- Word order (subject-verb-object)
- Politeness levels in language'
            ],
            'lo' => [
                'name' => 'Lao',
                'rules' => '- Verb conjugations and tense forms
- Honorific forms and respect levels
- Proper use of particles
- Word order (subject-verb-object)
- Tone markers and pronunciation'
            ],
            'zh' => [
                'name' => 'Chinese',
                'rules' => '- Subject-verb-object word order
- Proper use of particles (的/了/着/过)
- Measure words for counting
- Tone marks and pronunciation
- Formal vs informal language'
            ],
            'ja' => [
                'name' => 'Japanese',
                'rules' => '- Subject-object-verb word order
- Proper use of particles (は/が/を/に/で)
- Honorific forms (keigo)
- Verb conjugations and forms
- Politeness levels (desu/masu forms)'
            ],
            'ko' => [
                'name' => 'Korean',
                'rules' => '- Subject-object-verb word order
- Proper use of particles (은/는/이/가/을/를)
- Honorific forms and speech levels
- Verb conjugations and endings
- Politeness levels in language'
            ],
            'ar' => [
                'name' => 'Arabic',
                'rules' => '- Verb-subject-object word order
- Gender agreement (masculine/feminine)
- Case endings (i\'rab)
- Proper use of definite article (al-)
- Honorific forms and respect levels'
            ],
            'fa' => [
                'name' => 'Persian',
                'rules' => '- Subject-object-verb word order
- Proper use of prepositions and postpositions
- Honorific forms and respect levels
- Verb conjugations and tense forms
- Formal vs informal language (shoma/man)'
            ],
            'ur' => [
                'name' => 'Urdu',
                'rules' => '- Subject-object-verb word order
- Gender agreement (masculine/feminine)
- Honorific forms and respect levels
- Verb conjugations and tense forms
- Proper use of postpositions'
            ],
            'ru' => [
                'name' => 'Russian',
                'rules' => '- Gender agreement (masculine/feminine/neuter)
- Case system (nominative/accusative/dative/etc.)
- Verb conjugations and aspects
- Proper use of prepositions
- Formal vs informal address (ty/vy)'
            ],
            'uk' => [
                'name' => 'Ukrainian',
                'rules' => '- Gender agreement (masculine/feminine/neuter)
- Case system (nominative/accusative/dative/etc.)
- Verb conjugations and aspects
- Proper use of prepositions
- Formal vs informal address'
            ],
        ];

        return $instructions[$language] ?? [
            'name' => ucfirst($language),
            'rules' => '- General grammar rules
- Punctuation and formatting
- Sentence structure
- Language-specific conventions'
        ];
    }

    /**
     * Apply basic rule-based corrections
     */
    private function basicCorrections(string $text, string $language): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Basic punctuation fixes that work across languages
        $text = preg_replace('/\s*([.!?])\s*/', '$1 ', $text);
        $text = preg_replace('/\s*([,;:])\s*/', '$1 ', $text);

        // Fix multiple punctuation
        $text = preg_replace('/([.!?]){2,}/', '$1', $text);

        // Fix spacing around quotes
        $text = preg_replace('/"\s*/', '"', $text);
        $text = preg_replace('/\s*"/', '"', $text);

        return trim($text);
    }

    /**
     * Check if a language is supported
     */
    public function isLanguageSupported(string $language): bool
    {
        return isset($this->supportedLanguages[$language]);
    }

    /**
     * Get list of supported languages
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Get language name from code
     */
    public function getLanguageName(string $code): string
    {
        return $this->supportedLanguages[$code] ?? 'Unknown';
    }
}
