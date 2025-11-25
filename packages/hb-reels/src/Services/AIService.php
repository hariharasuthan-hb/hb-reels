<?php

namespace HbReels\EventReelGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AIService
{
    private ClientInterface $client;
    private bool $useGoogleTranslate;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 30,
        ]);
        
        // Enable Google Translate for better accuracy in non-English languages
        $this->useGoogleTranslate = config('eventreel.use_google_translate', true);
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

        // Enhanced AI analysis for better content understanding
        $prompt = "Analyze the following text and create optimized content for video generation.

CONTENT ANALYSIS:
1. Identify the event type: birthday, wedding, corporate, product launch, celebration, etc.
2. Determine the emotional tone: joyful, professional, romantic, energetic, elegant, etc.
3. Extract key visual elements: colors, lighting, setting, activities, people involved

CAPTION CREATION:
- Write a polished, engaging caption (1-3 lines)
- Make it suitable for video overlay text
- Maintain the original message's intent and tone

VIDEO SEARCH OPTIMIZATION:
- Provide 3-5 specific keywords for finding perfect stock footage
- Focus on visual and atmospheric elements that match the content
- Use terms that video search engines understand well
- Consider: lighting style, color palette, activity type, setting, mood

Return ONLY valid JSON in this exact format:
{
  \"caption\": \"Your polished caption text here\",
  \"video_keywords\": [\"keyword1\", \"keyword2\", \"keyword3\", \"keyword4\", \"keyword5\"],
  \"content_analysis\": {
    \"type\": \"birthday|wedding|corporate|celebration|other\",
    \"tone\": \"joyful|professional|elegant|energetic|romantic\",
    \"visual_elements\": \"bright colors|warm lighting|dramatic|natural|modern\"
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
     * Extract basic keywords from text for video search when AI is unavailable.
     */
    private function extractBasicKeywords(string $text): array
    {
        // Remove common words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them'];

        $words = str_word_count(strtolower($text), 1);
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 3;
        });

        // Add some contextual keywords based on common content types
        $contextualKeywords = [];
        $textLower = strtolower($text);

        if (strpos($textLower, 'birthday') !== false) {
            $contextualKeywords = ['birthday', 'celebration', 'party', 'cake', 'gifts'];
        } elseif (strpos($textLower, 'wedding') !== false) {
            $contextualKeywords = ['wedding', 'marriage', 'ceremony', 'bride', 'groom'];
        } elseif (strpos($textLower, 'corporate') !== false || strpos($textLower, 'business') !== false) {
            $contextualKeywords = ['corporate', 'business', 'meeting', 'professional', 'office'];
        } else {
            $contextualKeywords = ['celebration', 'event', 'gathering', 'people', 'happy'];
        }

        // Combine extracted keywords with contextual ones
        $allKeywords = array_merge(array_values($keywords), $contextualKeywords);
        return array_slice(array_unique($allKeywords), 0, 5); // Return up to 5 unique keywords
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
            \Log::info('Google Translate: Skipped', [
                'reason' => $sourceLanguage === $targetLanguage ? 'Same language' : 'Disabled in config',
                'text' => $text
            ]);
            return $text;
        }
        
        try {
            // Pre-process text for better translations
            $processedText = $this->preprocessForTranslation($text, $targetLanguage);

            \Log::info('>>> GOOGLE TRANSLATE REQUEST <<<', [
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'original_text' => $text,
                'processed_text' => $processedText,
                'input_length' => strlen($processedText),
                'input_encoding' => mb_detect_encoding($processedText)
            ]);

            $translator = new GoogleTranslate($targetLanguage);
            $translator->setSource($sourceLanguage);

            $translated = $translator->translate($processedText);
            
            \Log::info('>>> GOOGLE TRANSLATE RESPONSE <<<', [
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

