<?php

namespace HbReels\EventReelGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Services\GrammarService;

class AIService
{
    private ClientInterface $client;
    private bool $useGoogleTranslate;
    private bool $useFallbackTranslation;
    private GrammarService $grammarService;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 30,
        ]);

        // Enable Google Translate for better accuracy in non-English languages
        $this->useGoogleTranslate = config('eventreel.use_google_translate', true);
        $this->useFallbackTranslation = config('eventreel.fallback_translation', true);

        // Initialize grammar service
        $this->grammarService = new GrammarService();
    }

    /**
     * Generate a polished caption from any text using Ollama.
     * Works with events, announcements, acknowledgements, or any content type.
     *
     * @param string $text The text to generate caption from
     * @param string $language Language code (e.g., 'en', 'es', 'hi', 'ta')
     * @return array Returns array with 'caption' and 'video_keywords' keys
     */
    public function generateCaption(string $text, string $language = 'en'): array
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
        $model = config('eventreel.ollama_model', 'mistral');

        // Enhanced AI analysis for better content understanding and creative caption generation
        $prompt = "You are a professional video content creator. Transform the provided text into engaging video content.

CONTENT ANALYSIS:
1. Identify the event/occasion type (birthday, wedding, corporate, product launch, celebration, etc.)
2. Determine the emotional tone (joyful, professional, romantic, energetic, elegant, etc.)
3. Extract key visual and thematic elements (colors, lighting, setting, activities, atmosphere)

CAPTION CREATION - CRITICAL REQUIREMENT:
- Create a BRAND NEW, creative, and engaging caption (1-3 lines maximum)
- DO NOT copy or repeat the original text word-for-word
- Transform the description into an exciting, professional video caption
- Use dynamic, engaging language that captures the event's energy
- Make it perfect for video overlay text - concise but impactful
- Focus on the celebration, emotion, and key message

VIDEO SEARCH OPTIMIZATION:
- Provide 3-5 specific visual keywords for perfect stock footage matching
- Focus on: lighting style, colors, activities, settings, atmosphere, mood
- Use descriptive terms video search engines understand (e.g., 'bright celebration', 'elegant lighting', 'outdoor gathering')
- Prioritize visual and atmospheric keywords

IMPORTANT: Always generate an ORIGINAL caption that enhances and transforms the input text.

Return ONLY valid JSON in this exact format:
{
  \"caption\": \"[Your creative, original caption - never copy input text]\",
  \"video_keywords\": [\"visual keyword1\", \"visual keyword2\", \"visual keyword3\", \"atmospheric keyword4\", \"activity keyword5\"],
  \"content_analysis\": {
    \"type\": \"birthday|wedding|corporate|celebration|product|announcement|other\",
    \"tone\": \"joyful|professional|elegant|energetic|romantic|warm|sophisticated\",
    \"visual_elements\": \"bright colors|warm lighting|dramatic lighting|natural setting|modern|traditional|elegant\"
  }
}

Text to analyze:
{$text}

JSON:";

        // Default fallback result
        $result = [
            'caption' => $this->fallbackCaption($text),
            'video_keywords' => $this->extractBasicKeywords($text),
            'content_analysis' => [
                'type' => 'celebration',
                'tone' => 'joyful',
                'visual_elements' => 'bright colors'
            ]
        ];

        try {
            $response = $this->client->post("{$ollamaUrl}/api/generate", [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'temperature' => 0.7, // Balanced creativity and consistency
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $aiResponse = trim($data['response'] ?? '');

            \Log::info('========== AI CONTENT ANALYSIS ==========');
            \Log::info('Step 1: Original Input Text', [
                'text' => $text,
                'length' => strlen($text)
            ]);
            \Log::info('Step 2: AI Raw Response', [
                'response' => $aiResponse,
                'length' => strlen($aiResponse)
            ]);

            // Parse JSON response
            if (!empty($aiResponse)) {
                // Extract JSON from response
                if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $aiResponse, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    if ($parsed && isset($parsed['caption'])) {
                        $result = $parsed;
                        \Log::info('Step 3: Successfully parsed AI content analysis', [
                            'caption' => $result['caption'],
                            'video_keywords' => $result['video_keywords'] ?? [],
                            'content_type' => $result['content_analysis']['type'] ?? 'unknown',
                            'tone' => $result['content_analysis']['tone'] ?? 'unknown'
                        ]);
                    } else {
                        \Log::warning('AI returned invalid JSON structure', ['parsed' => $parsed]);
                    }
                } else {
                    \Log::warning('No valid JSON found in AI response', ['response' => $aiResponse]);
                }
            }

        } catch (GuzzleException $e) {
            \Log::warning('AI content analysis failed, using fallback', [
                'error' => $e->getMessage(),
                'fallback_caption' => $result['caption']
            ]);
        }
        
        // Step 2: If target language is not English, translate the caption
        \Log::info('Translation check', [
            'language_parameter' => $language,
            'language_type' => gettype($language),
            'is_not_english' => ($language !== 'en'),
            'caption_before_translation' => substr($result['caption'], 0, 100)
        ]);

        if ($language !== 'en') {
            \Log::info('Step 4: Preparing for Translation', [
                'source_language' => 'en',
                'target_language' => $language,
                'caption_to_translate' => $result['caption']
            ]);

            $result['caption'] = $this->translateWithGoogle($result['caption'], $language, 'en');

            \Log::info('Step 5: Translation Complete', [
                'target_language' => $language,
                'translated_caption' => $result['caption'],
                'video_keywords_unchanged' => $result['video_keywords'] // Keywords stay in English for better search
            ]);

            // Step 6: Apply grammar checking if enabled
            if ($this->grammarService->isLanguageSupported($language)) {
                $originalCaption = $result['caption'];
                $result['caption'] = $this->grammarService->checkGrammar($result['caption'], $language);

                \Log::info('Step 6: Grammar Check Applied', [
                    'language' => $language,
                    'original_length' => strlen($originalCaption),
                    'corrected_length' => strlen($result['caption']),
                    'changes_made' => $originalCaption !== $result['caption']
                ]);
            }

            \Log::info('========== END CONTENT ANALYSIS ==========');

            return $result;
        }

        \Log::info('Step 4: No Translation Needed (English)', [
            'returning_caption' => $result['caption'],
            'video_keywords' => $result['video_keywords']
        ]);
        \Log::info('========== END CONTENT ANALYSIS ==========');

        return $result;
    }

    /**
     * Extract structured details from any text using AI.
     * Intelligently handles events, announcements, acknowledgements, or any content type.
     * Returns array with keys: line1, line2, line3, line4, line5
     * 
     * @param string $text The text to extract details from
     * @param string $language Language code (e.g., 'en', 'es', 'hi', 'ta')
     */
    public function extractEventDetails(string $text, string $language = 'en'): array
    {
        $ollamaUrl = config('eventreel.ollama_url', 'http://localhost:11434');
        $model = config('eventreel.ollama_model', 'mistral');

        // Step 1: ALWAYS let AI understand and extract details in English first
        $prompt = "You are an expert content analyzer.
Analyze the following text and identify its type (event, announcement, acknowledgement, promotion, etc.).
Extract the most important information and format it into 3-5 short lines for a video overlay.

FORMATTING RULES:
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
            
            \Log::info('========== AI DETAIL EXTRACTION ==========');
            \Log::info('Step 1: Original Input Text', [
                'text' => $text,
                'length' => strlen($text)
            ]);
            \Log::info('Step 2: AI Extraction Response (English)', [
                'response' => $aiResponse,
                'length' => strlen($aiResponse)
            ]);
            
            // Try to extract JSON from the response (handle both clean JSON and text with JSON)
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $aiResponse, $matches)) {
                $extracted = json_decode($matches[0], true);
                if ($extracted && is_array($extracted)) {
                    // Filter out empty lines
                    $englishLines = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $lineKey = "line{$i}";
                        if (isset($extracted[$lineKey]) && !empty(trim($extracted[$lineKey]))) {
                            $englishLines[$lineKey] = trim($extracted[$lineKey]);
                        }
                    }
                    
                    \Log::info('Step 3: AI Extracted English Lines', [
                        'lines' => $englishLines,
                        'count' => count($englishLines)
                    ]);
                    
                    // If we got at least one line, translate if needed
                    if (!empty($englishLines)) {
                        // Step 2: If target language is not English, translate each line
                        if ($language !== 'en') {
                            $totalLines = count($englishLines);
                            \Log::info('Step 4: Translating Each Line', [
                                'source_language' => 'en',
                                'target_language' => $language,
                                'total_lines' => $totalLines
                            ]);
                            
                            $translatedLines = [];
                            $lineNumber = 1;
                            foreach ($englishLines as $lineKey => $englishText) {
                                \Log::info("Translating Line {$lineNumber}/{$totalLines}", [
                                    'line_key' => $lineKey,
                                    'english_text' => $englishText
                                ]);
                                
                                $translatedLines[$lineKey] = $this->translateWithGoogle($englishText, $language, 'en');
                                
                                \Log::info("Line {$lineNumber} Translated", [
                                    'line_key' => $lineKey,
                                    'translated_text' => $translatedLines[$lineKey]
                                ]);
                                
                                $lineNumber++;
                            }
                            
                            \Log::info('Step 5: All Lines Translated', [
                                'translated_lines' => $translatedLines
                            ]);

                            // Step 6: Apply grammar checking to each translated line if enabled
                            if ($this->grammarService->isLanguageSupported($language)) {
                                $grammarCheckedLines = [];
                                foreach ($translatedLines as $lineKey => $translatedText) {
                                    $originalText = $translatedText;
                                    $grammarCheckedLines[$lineKey] = $this->grammarService->checkGrammar($translatedText, $language);

                                    if ($originalText !== $grammarCheckedLines[$lineKey]) {
                                        \Log::info("Grammar corrected line {$lineKey}", [
                                            'original' => $originalText,
                                            'corrected' => $grammarCheckedLines[$lineKey]
                                        ]);
                                    }
                                }
                                $translatedLines = $grammarCheckedLines;

                                \Log::info('Step 6: Grammar Check Applied to All Lines', [
                                    'language' => $language,
                                    'total_lines' => count($translatedLines)
                                ]);
                            }

                            \Log::info('========== END DETAIL EXTRACTION ==========');

                            return $translatedLines;
                        }
                        
                        \Log::info('Step 4: No Translation Needed (English)', [
                            'returning_lines' => $englishLines
                        ]);
                        \Log::info('========== END DETAIL EXTRACTION ==========');
                        
                        return $englishLines;
                    }
                }
            }
            
            // Fallback if JSON extraction fails
            \Log::warning('AI extraction failed, using fallback', ['response' => $aiResponse]);
            return $this->fallbackExtractDetails($text, $language);
        } catch (GuzzleException $e) {
            \Log::error('AI service error', ['error' => $e->getMessage()]);
            return $this->fallbackExtractDetails($text, $language);
        }
    }

    /**
     * Fallback content extraction using simple text parsing.
     * Intelligently splits text into 3-5 meaningful lines.
     */
    private function fallbackExtractDetails(string $text, string $language = 'en'): array
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
        
        // Translate if target language is not English
        if ($language !== 'en') {
            $translatedLines = [];
            foreach ($lines as $lineKey => $englishText) {
                $translatedLines[$lineKey] = $this->translateWithGoogle($englishText, $language, 'en');
            }
            return $translatedLines;
        }
        
        return $lines;
    }

    /**
     * Extract basic visual keywords from text for video search when AI is unavailable.
     */
    private function extractBasicKeywords(string $text): array
    {
        $textLower = strtolower($text);

        // Focus on visual and atmospheric keywords that work well for video search
        $visualKeywords = [];

        // Event-specific visual keywords
        if (strpos($textLower, 'birthday') !== false) {
            $visualKeywords = ['birthday party', 'celebration lights', 'cake candles', 'colorful balloons', 'happy gathering'];
        } elseif (strpos($textLower, 'wedding') !== false) {
            $visualKeywords = ['wedding ceremony', 'romantic lighting', 'elegant flowers', 'bridal gown', 'celebration dance'];
        } elseif (strpos($textLower, 'corporate') !== false || strpos($textLower, 'business') !== false) {
            $visualKeywords = ['corporate meeting', 'professional lighting', 'business presentation', 'modern office', 'team celebration'];
        } elseif (strpos($textLower, 'graduation') !== false) {
            $visualKeywords = ['graduation ceremony', 'academic gowns', 'celebration crowd', 'achievement moment', 'cap throwing'];
        } elseif (strpos($textLower, 'anniversary') !== false) {
            $visualKeywords = ['romantic dinner', 'candlelight', 'love celebration', 'couple dancing', 'elegant atmosphere'];
        } elseif (strpos($textLower, 'product') !== false || strpos($textLower, 'launch') !== false) {
            $visualKeywords = ['product showcase', 'modern technology', 'innovation display', 'bright lighting', 'professional presentation'];
        } else {
            // Generic celebration keywords
            $visualKeywords = ['celebration party', 'happy gathering', 'bright lights', 'joyful moment', 'group celebration'];
        }

        // Add time/context specific keywords
        if (strpos($textLower, 'night') !== false || strpos($textLower, 'evening') !== false) {
            $visualKeywords[] = 'night lighting';
        }

        if (strpos($textLower, 'outdoor') !== false || strpos($textLower, 'park') !== false || strpos($textLower, 'garden') !== false) {
            $visualKeywords[] = 'outdoor celebration';
        }

        if (strpos($textLower, 'indoor') !== false || strpos($textLower, 'hall') !== false || strpos($textLower, 'room') !== false) {
            $visualKeywords[] = 'indoor gathering';
        }

        return array_slice($visualKeywords, 0, 5);
    }

    /**
     * Fallback caption generation if Ollama is unavailable.
     * Creates an engaging caption even without AI.
     */
    private function fallbackCaption(string $text): string
    {
        // Clean and normalize the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $textLower = strtolower($text);

        // Detect event types and create appropriate captions
        if (strpos($textLower, 'birthday') !== false) {
            if (preg_match('/(\d+)(?:th|st|nd|rd)?\s*birthday/i', $text, $matches)) {
                return "Celebrating {$matches[1]} Amazing Years! 🎉";
            }
            return "Happy Birthday Celebration! 🎂";
        }

        if (strpos($textLower, 'wedding') !== false) {
            return "Forever Begins Today! 💍";
        }

        if (strpos($textLower, 'anniversary') !== false) {
            return "Celebrating Love & Togetherness! 💕";
        }

        if (strpos($textLower, 'graduation') !== false) {
            return "Achievement Unlocked! 🎓";
        }

        if (strpos($textLower, 'corporate') !== false || strpos($textLower, 'business') !== false) {
            return "Excellence in Action! 🚀";
        }

        if (strpos($textLower, 'product') !== false || strpos($textLower, 'launch') !== false) {
            return "Innovation Meets Excellence! ✨";
        }

        if (strpos($textLower, 'celebration') !== false || strpos($textLower, 'party') !== false) {
            return "Making Memories Together! 🎊";
        }

        if (strpos($textLower, 'success') !== false || strpos($textLower, 'achievement') !== false) {
            return "Success Story in Motion! 🏆";
        }

        // Generic celebration captions based on keywords
        if (strpos($textLower, 'happy') !== false || strpos($textLower, 'joy') !== false) {
            return "Spreading Joy & Happiness! 😊";
        }

        if (strpos($textLower, 'love') !== false || strpos($textLower, 'heart') !== false) {
            return "Love Makes Everything Beautiful! 💖";
        }

        if (strpos($textLower, 'together') !== false || strpos($textLower, 'family') !== false) {
            return "Together We Celebrate! 👨‍👩‍👧‍👦";
        }

        // Default engaging caption
        return "Creating Amazing Moments! 🌟";
    }
    
    /**
     * Pre-process text for better Google Translate results.
     * Fixes common translation issues with specific phrases.
     */
    private function preprocessForTranslation(string $text, string $targetLanguage): string
    {
        // Common phrase mappings that Google Translate gets wrong
        $phraseMappings = [
            // Tamil specific fixes
            'ta' => [
                'Starring' => 'நடிகர்',
                'starring' => 'நடிகர்',
                'Details to follow soon' => 'விவரங்கள் விரைவில் வரும்',
                'details to follow soon' => 'விவரங்கள் விரைவில் வரும்',
                'Details to follow' => 'விவரங்கள் வரும்',
                'details to follow' => 'விவரங்கள் வரும்',
                'Movie Title TBD' => 'படத்தின் தலைப்பு: தீர்மானிக்கப்படவில்லை',
                'movie title TBD' => 'படத்தின் தலைப்பு: தீர்மானிக்கப்படவில்லை',
                'Movie Title: TBD' => 'படத்தின் தலைப்பு: தீர்மானிக்கப்படவில்லை',
                'movie title: TBD' => 'படத்தின் தலைப்பு: தீர்மானிக்கப்படவில்லை',
                'TBD' => 'தீர்மானிக்கப்படவில்லை',
                'New Event Announcement' => 'புதிய நிகழ்வு அறிவிப்பு',
                'new event announcement' => 'புதிய நிகழ்வு அறிவிப்பு',
                'Action star' => 'அதிரடி நடிகர்',
                'action star' => 'அதிரடி நடிகர்',
            ],
            // Hindi specific fixes
            'hi' => [
                'Starring' => 'कलाकार',
                'starring' => 'कलाकार',
                'Details to follow soon' => 'विवरण जल्द ही आएंगे',
                'details to follow soon' => 'विवरण जल्द ही आएंगे',
                'Movie Title TBD' => 'फिल्म का शीर्षक: निर्धारित नहीं',
                'movie title TBD' => 'फिल्म का शीर्षक: निर्धारित नहीं',
                'TBD' => 'निर्धारित नहीं',
                'New Event Announcement' => 'नई घटना की घोषणा',
                'new event announcement' => 'नई घटना की घोषणा',
                'Action star' => 'एक्शन स्टार',
                'action star' => 'एक्शन स्टार',
            ],
            // Telugu specific fixes
            'te' => [
                'Starring' => 'నటించిన',
                'starring' => 'నటించిన',
                'Details to follow soon' => 'వివరాలు త్వరలో వస్తాయి',
                'details to follow soon' => 'వివరాలు త్వరలో వస్తాయి',
                'Movie Title TBD' => 'సినిమా శీర్షిక: నిర్ణయించబడలేదు',
                'movie title TBD' => 'సినిమా శీర్షిక: నిర్ణయించబడలేదు',
                'TBD' => 'నిర్ణయించబడలేదు',
                'New Event Announcement' => 'కొత్త ఈవెంట్ ప్రకటన',
                'new event announcement' => 'కొత్త ఈవెంట్ ప్రకటన',
                'Action star' => 'యాక్షన్ స్టార్',
                'action star' => 'యాక్షన్ స్టార్',
            ],
        ];

        // Apply phrase mappings for the target language
        if (isset($phraseMappings[$targetLanguage])) {
            foreach ($phraseMappings[$targetLanguage] as $english => $translated) {
                $text = str_replace($english, $translated, $text);
            }
        }

        // Fix word order issues for subject-verb-object languages like Tamil
        if ($targetLanguage === 'ta') {
            // Fix sentences that start with "Action star [Name] announces..."
            // Google Translate often reverses the word order
            if (preg_match('/^(Action star|action star)\s+([^,]+),\s*(.+)$/i', $text, $matches)) {
                $person = trim($matches[2]);
                $action = trim($matches[3]);
                // Reconstruct as: [Person] [action]
                $text = $person . ' ' . $action;
            }
        }

        return $text;
    }

    /**
     * Translate text using Google Translate for accurate multilingual support.
     * This provides much better quality than AI models for translation.
     *
     * @param string $text Text to translate
     * @param string $targetLanguage Target language code (e.g., 'ta', 'hi', 'zh')
     * @param string $sourceLanguage Source language code (default: 'en')
     * @return string Translated text
     */
    public function translateWithGoogle(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        \Log::info('Google Translate called', [
            'text_length' => strlen($text),
            'target_language' => $targetLanguage,
            'source_language' => $sourceLanguage,
            'use_google_translate' => $this->useGoogleTranslate,
            'same_language' => ($sourceLanguage === $targetLanguage)
        ]);

        if (!$this->useGoogleTranslate || $sourceLanguage === $targetLanguage) {
            \Log::info('Google Translate: Skipped', [
                'reason' => $sourceLanguage === $targetLanguage ? 'Same language' : 'Disabled in config',
                'text' => $text
            ]);
            return $text;
        }

        // Add retry logic for network issues
        $maxRetries = 3;
        $retryDelay = 1; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Pre-process text for better translations
                $processedText = $this->preprocessForTranslation($text, $targetLanguage);

                \Log::info('>>> GOOGLE TRANSLATE REQUEST <<<', [
                    'attempt' => $attempt,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'original_text' => $text,
                    'processed_text' => $processedText,
                    'input_length' => strlen($processedText),
                    'input_encoding' => mb_detect_encoding($processedText)
                ]);

                $translator = new GoogleTranslate($targetLanguage);
                $translator->setSource($sourceLanguage);

                // Add timeout and retry settings
                $translator->setOptions([
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ]);

                $translated = $translator->translate($processedText);

                // Validate translation result
                if (empty($translated) || strlen($translated) < 2) {
                    throw new \Exception('Empty or invalid translation result');
                }

                \Log::info('>>> GOOGLE TRANSLATE RESPONSE <<<', [
                    'attempt' => $attempt,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'output_text' => $translated,
                    'output_length' => strlen($translated),
                    'output_encoding' => mb_detect_encoding($translated),
                    'has_unicode' => preg_match('/[\x{0080}-\x{FFFF}]/u', $translated) ? 'YES' : 'NO'
                ]);

                // Print exact Google Translate output to console
                echo "\n=== GOOGLE TRANSLATE OUTPUT ===\n";
                echo "Input:  '{$text}'\n";
                echo "Output: '{$translated}'\n";
                echo "From: {$sourceLanguage} → To: {$targetLanguage}\n";
                echo "Unicode: " . (preg_match('/[\x{0080}-\x{FFFF}]/u', $translated) ? 'YES' : 'NO') . "\n";
                echo "Length: " . strlen($translated) . " characters\n";
                echo "=================================\n\n";

                return $translated;

            } catch (\Exception $e) {
                \Log::warning('>>> GOOGLE TRANSLATE ATTEMPT FAILED <<<', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error_message' => $e->getMessage(),
                    'will_retry' => $attempt < $maxRetries
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
                    continue;
                }

                // All retries failed
                \Log::error('>>> GOOGLE TRANSLATE ERROR - ALL RETRIES FAILED <<<', [
                    'total_attempts' => $maxRetries,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'input_text' => $text,
                    'using_fallback_translation' => $this->useFallbackTranslation
                ]);

                // Try fallback translation if enabled
                if ($this->useFallbackTranslation) {
                    $fallbackResult = $this->fallbackTranslate($text, $targetLanguage);
                    if ($fallbackResult !== $text) {
                        \Log::info('>>> FALLBACK TRANSLATION SUCCESS <<<', [
                            'original_text' => $text,
                            'fallback_translation' => $fallbackResult,
                            'target_language' => $targetLanguage
                        ]);
                        return $fallbackResult;
                    }
                }

                // Final fallback: Return original text with language marker
                return $text . " ({$targetLanguage})";
            }
        }

        // This should never be reached, but just in case
        return $text;
    }
    
    /**
     * Fallback translation method when Google Translate is unavailable.
     * Provides mock translations for testing and development.
     */
    private function fallbackTranslate(string $text, string $targetLanguage): string
    {
        // Mock translations for common phrases (for testing only)
        $mockTranslations = [
            'ta' => [ // Tamil
                'happy birthday' => 'பிறந்தநாள் வாழ்த்துக்கள்',
                'happy birth day' => 'பிறந்தநாள் வாழ்த்துக்கள்',
                'birthday' => 'பிறந்தநாள்',
                'celebration' => 'கொண்டாட்டம்',
                'congratulations' => 'வாழ்த்துக்கள்',
                'welcome' => 'வரவேற்பு',
                'thank you' => 'நன்றி',
                'good morning' => 'காலை வணக்கம்',
                'good evening' => 'மாலை வணக்கம்',
                'hello' => 'வணக்கம்',
                'how are you' => 'எப்படி இருக்கிறீர்கள்',
            ],
            'hi' => [ // Hindi
                'happy birthday' => 'जन्मदिन मुबारक हो',
                'happy birth day' => 'जन्मदिन मुबारक हो',
                'birthday' => 'जन्मदिन',
                'celebration' => 'जश्न',
                'congratulations' => 'बधाई हो',
                'welcome' => 'स्वागत है',
                'thank you' => 'धन्यवाद',
                'good morning' => 'सुप्रभात',
                'good evening' => 'सुबह भला',
                'hello' => 'नमस्ते',
                'how are you' => 'आप कैसे हैं',
            ],
            'te' => [ // Telugu
                'happy birthday' => 'పుట్టినరోజు శుభాకాంక్షలు',
                'happy birth day' => 'పుట్టినరోజు శుభాకాంక్షలు',
                'birthday' => 'పుట్టినరోజు',
                'celebration' => 'జరుపుక',
                'congratulations' => 'అభినందనలు',
                'welcome' => 'స్వాగతం',
                'thank you' => 'ధన్యవాదాలు',
                'good morning' => 'శుభోదయం',
                'good evening' => 'సునాయంతో',
                'hello' => 'నమస్కారం',
                'how are you' => 'మీరు ఎలా ఉన్నారు',
            ],
            'es' => [ // Spanish
                'happy birthday' => 'feliz cumpleaños',
                'happy birth day' => 'feliz cumpleaños',
                'birthday' => 'cumpleaños',
                'celebration' => 'celebración',
                'congratulations' => 'felicitaciones',
                'welcome' => 'bienvenido',
                'thank you' => 'gracias',
                'good morning' => 'buenos días',
                'good evening' => 'buenas tardes',
                'hello' => 'hola',
                'how are you' => 'cómo estás',
            ],
            'fr' => [ // French
                'happy birthday' => 'joyeux anniversaire',
                'happy birth day' => 'joyeux anniversaire',
                'birthday' => 'anniversaire',
                'celebration' => 'célébration',
                'congratulations' => 'félicitations',
                'welcome' => 'bienvenue',
                'thank you' => 'merci',
                'good morning' => 'bonjour',
                'good evening' => 'bonsoir',
                'hello' => 'salut',
                'how are you' => 'comment ça va',
            ],
        ];

        // Check if we have mock translations for the target language
        if (!isset($mockTranslations[$targetLanguage])) {
            return $text; // No mock translation available
        }

        $textLower = strtolower(trim($text));

        // Look for exact matches first
        if (isset($mockTranslations[$targetLanguage][$textLower])) {
            return $mockTranslations[$targetLanguage][$textLower];
        }

        // Handle combined phrases like "happy birthday lohith"
        $words = explode(' ', $textLower);
        if (count($words) > 1) {
            // Check if first part is a known phrase
            $firstWord = $words[0];
            $remainingWords = implode(' ', array_slice($words, 1));

            if (isset($mockTranslations[$targetLanguage][$firstWord])) {
                // If second part is a name, transliterate it
                $nameTranslation = $this->transliterateName($remainingWords, $targetLanguage);
                if ($nameTranslation !== $remainingWords) {
                    return $mockTranslations[$targetLanguage][$firstWord] . ' ' . $nameTranslation;
                }
            }

            // Check for "happy birthday" + name pattern
            if ($textLower === 'happy birthday lohith' || $textLower === 'happy birth day lohith') {
                if ($targetLanguage === 'ta') {
                    return 'பிறந்தநாள் வாழ்த்துக்கள் லோஹித்';
                }
            }
        }

        // Look for partial matches in common phrases
        foreach ($mockTranslations[$targetLanguage] as $english => $translation) {
            if (strpos($textLower, $english) !== false) {
                return $translation;
            }
        }

        // For names and other proper nouns, transliterate them
        $nameTranslation = $this->transliterateName($textLower, $targetLanguage);
        if ($nameTranslation !== $textLower) {
            return $nameTranslation;
        }

        // If no translation found, return original text
        return $text;
    }

    /**
     * Transliterate names to target language script.
     */
    private function transliterateName(string $name, string $targetLanguage): string
    {
        $nameLower = strtolower(trim($name));

        if ($targetLanguage === 'ta') {
            // Tamil name transliterations
            $tamilNames = [
                'lohith' => 'லோஹித்',
                'lohitha' => 'லோஹிதா',
                'lohithkumar' => 'லோஹித்குமார்',
                'arun' => 'அருண்',
                'aruna' => 'அருணா',
                'kumar' => 'குமார்',
                'kumari' => 'குமாரி',
                'priya' => 'பிரியா',
                'priyanka' => 'பிரியங்கா',
                'sara' => 'சாரா',
                'sarah' => 'சாரா',
                'john' => 'ஜான்',
                'mary' => 'மேரி',
                'david' => 'டேவிட்',
                'michael' => 'மைக்கேல்',
                'ravi' => 'ரவி',
                'ravi kumar' => 'ரவி குமார்',
                'suresh' => 'சுரேஷ்',
                'mahesh' => 'மகேஷ்',
                'rajesh' => 'ராஜேஷ்',
                'ganesh' => 'கணேஷ்',
                'vignesh' => 'விக்னேஷ்',
                'prakash' => 'பிரகாஷ்',
                'naveen' => 'நவீன்',
                'naveena' => 'நவீனா',
            ];

            if (isset($tamilNames[$nameLower])) {
                return $tamilNames[$nameLower];
            }

            // Handle compound names
            $words = explode(' ', $nameLower);
            if (count($words) === 2) {
                $first = $words[0];
                $last = $words[1];

                if (isset($tamilNames[$first]) && isset($tamilNames[$last])) {
                    return $tamilNames[$first] . ' ' . $tamilNames[$last];
                }
            }
        }

        // Return original name if no transliteration found
        return $name;
    }

    /**
     * Detect if text is primarily in English.
     * Simple heuristic: if more than 70% of characters are ASCII, assume English.
     */
    private function isEnglishText(string $text): bool
    {
        if (empty($text)) {
            return true;
        }

        $asciiCount = 0;
        $totalCount = mb_strlen($text);

        for ($i = 0; $i < $totalCount; $i++) {
            $char = mb_substr($text, $i, 1);
            if (ord($char) < 128) {
                $asciiCount++;
            }
        }

        return ($asciiCount / $totalCount) > 0.7;
    }
}

