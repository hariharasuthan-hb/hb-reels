<?php

namespace HbReels\EventReelGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Stichoza\GoogleTranslate\GoogleTranslate;
use HbReels\EventReelGenerator\Services\GrammarService;

class AIService
{
    private ClientInterface $client;
    private bool $useGoogleTranslate;
    private GrammarService $grammarService;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 120,
            'connect_timeout' => 10,
        ]);
        
        // Enable Google Translate for better accuracy in non-English languages
        $this->useGoogleTranslate = config('eventreel.use_google_translate', true);

        // Initialize grammar service for text correction
        $this->grammarService = app(GrammarService::class);
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

        // Check if this is a product description about HBPartner.ai Event Reel Generator
        $textLower = strtolower($text);
        if ((strpos($textLower, 'ai-powered') !== false ||
             strpos($textLower, 'ai powered') !== false ||
             strpos($textLower, 'event reel') !== false ||
             strpos($textLower, 'event-reel') !== false ||
             strpos($textLower, 'reel generator') !== false) &&
            (strpos($textLower, 'creates pro reels') !== false ||
             strpos($textLower, 'creates professional') !== false ||
             strpos($textLower, 'flyers or details') !== false ||
             strpos($textLower, 'identifies event') !== false ||
             strpos($textLower, 'polished videos') !== false ||
             strpos($textLower, 'animations') !== false ||
             strpos($textLower, 'saves time') !== false ||
             strpos($textLower, 'boosts engagement') !== false)) {

            // This is a product description - create engaging promotional content
            $text = "Discover the revolutionary AI-powered Event Reel Generator that transforms simple event flyers and text into stunning professional video reels! Our advanced AI automatically identifies event details like names, dates, venues, and key highlights. Create polished videos with smooth animations, perfect background music, and cinematic effects. Save hours of editing time while dramatically boosting audience engagement with professional-quality content that brings every special moment to life!";
        }

        // Enhanced AI analysis for better content understanding and creative caption generation
        $prompt = "You are a professional video content creator. Transform the provided text into engaging video content.

CONTENT ANALYSIS:
1. Identify the event/occasion type (birthday, wedding, corporate, product launch, celebration, etc.)
2. Determine the emotional tone (joyful, professional, romantic, energetic, elegant, etc.)
3. Extract key visual and thematic elements (colors, lighting, setting, activities, atmosphere)

CAPTION CREATION - CRITICAL REQUIREMENTS:
- Create a BRAND NEW, creative, and engaging caption
- For product announcements, pricing, or contact info: Include ALL details (prices, phone numbers, contact info) - DO NOT truncate
- DO NOT copy or repeat the original text word-for-word (unless it's essential information like prices/contact details)
- DO NOT add any explanations, comments, or notes in the caption
- DO NOT include text like \"(This is...)\" or \"(No corrections needed)\" in the caption
- DO NOT truncate with \"...\" - show FULL text
- The caption field must contain ONLY the actual caption text, nothing else
- Transform the description into an exciting, professional video caption
- Use dynamic, engaging language that captures the event's energy
- Make it perfect for video overlay text - include all important information
- Focus on the celebration, emotion, and key message
- For announcements with contact info, prices, or details: preserve ALL information completely

VIDEO SEARCH OPTIMIZATION:
- Provide 3-5 specific visual keywords for perfect stock footage matching
- Focus on: lighting style, colors, activities, settings, atmosphere, mood
- Use descriptive terms video search engines understand (e.g., 'bright celebration', 'elegant lighting', 'outdoor gathering')
- Prioritize visual and atmospheric keywords

IMPORTANT: 
- Always generate an ORIGINAL caption that enhances and transforms the input text
- The caption must be clean text only - no explanations, no parentheses with notes, no comments

Return ONLY valid JSON in this exact format:
{
  \"caption\": \"[Your creative, original caption - clean text only, no explanations]\",
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
                    'options' => [
                        'num_predict' => 80,   // captions don't need more
                        'temperature' => 0.4,
                    ],
                ],
                'timeout' => 120,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $aiResponse = trim($data['response'] ?? '');

            // Parse JSON response
            if (!empty($aiResponse)) {
                // Extract JSON from response
                if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $aiResponse, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    if ($parsed && isset($parsed['caption'])) {
                        // Clean the caption to remove any explanation text
                        $caption = trim($parsed['caption']);
                        
                        // Remove explanatory text in parentheses
                        $caption = preg_replace('/\s*\([^)]*\)\s*/u', '', $caption);
                        
                        // Remove common explanation patterns
                        $explanationPatterns = [
                            '/\s*\(This sentence is already grammatically correct\.?\)\s*/iu',
                            '/\s*\(No corrections needed\.?\)\s*/iu',
                            '/\s*\(Already correct\.?\)\s*/iu',
                            '/\s*\(Grammatically correct\.?\)\s*/iu',
                            '/\s*\(No changes needed\.?\)\s*/iu',
                            '/\s*\(Correct as is\.?\)\s*/iu',
                        ];
                        
                        foreach ($explanationPatterns as $pattern) {
                            $caption = preg_replace($pattern, '', $caption);
                        }
                        
                        // Clean up multiple spaces
                        $caption = preg_replace('/\s+/', ' ', $caption);
                        $caption = trim($caption);
                        
                        $parsed['caption'] = $caption;
                        $result = $parsed;
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
        if ($language !== 'en') {
            $result['caption'] = $this->translateWithGoogle($result['caption'], $language, 'en');
            return $result;
        }

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
1. Extract the most important information based on the content type
2. For EVENTS: Include title, date/time, location, highlights, call-to-action
3. For ANNOUNCEMENTS: Include main message, details, date (if any), call-to-action
4. For ACKNOWLEDGEMENTS: Include who is being acknowledged, reason, appreciation message
5. For GENERAL content: Extract key points in logical order
6. If information is missing, skip that line (don't use 'TBA')
7. Include FULL text - do not truncate or abbreviate. The system will handle text wrapping automatically.
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
                    'options' => [
                        'num_predict' => 200,   // Increased to allow longer captions
                        'temperature' => 0.4,
                    ],
                ],
                'timeout' => 120,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $aiResponse = trim($data['response'] ?? '');
            
            
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
                    
                    // If we got at least one line, apply grammar checking then translate if needed
                    if (!empty($englishLines)) {
                        // Apply AI grammar checking to each extracted line
                        foreach ($englishLines as $lineKey => $lineText) {
                            $originalLine = $lineText;
                            $englishLines[$lineKey] = $this->grammarService->checkGrammar($lineText, 'en');

                            if ($originalLine !== $englishLines[$lineKey]) {
                                // Grammar was corrected
                            }
                        }

                        // Step 2: If target language is not English, translate each line
                        if ($language !== 'en') {
                            $totalLines = count($englishLines);
                            
                            $translatedLines = [];
                            $lineNumber = 1;
                            foreach ($englishLines as $lineKey => $englishText) {
                                $translatedLines[$lineKey] = $this->translateWithGoogle($englishText, $language, 'en');
                                $lineNumber++;
                            }
                            
                            return $translatedLines;
                        }
                        
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
                // Apply grammar checking to each line
                $processedLine = $this->grammarService->checkGrammar($sentences[$i], $language);

                // Don't truncate - let VideoRenderer handle text wrapping
                // Full text will be displayed and wrapped automatically
                $lines["line" . ($i + 1)] = $processedLine;
            }
        }
        
        // Ensure we have at least one line
        if (empty($lines)) {
            $lines['line1'] = $text; // Don't truncate - show full text
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
    private function translateWithGoogle(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        if (!$this->useGoogleTranslate || $sourceLanguage === $targetLanguage) {
            return $text;
        }
        
        try {
            // Pre-process text for better translations
            $processedText = $this->preprocessForTranslation($text, $targetLanguage);

            $translator = new GoogleTranslate($targetLanguage);
            $translator->setSource($sourceLanguage);

            $translated = $translator->translate($processedText);

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
            \Log::error('>>> GOOGLE TRANSLATE ERROR <<<', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'input_text' => $text,
                'falling_back_to_original' => true
            ]);
            
            // Fallback to original text if translation fails
            return $text;
        }
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

