<?php

namespace HbReels\EventReelGenerator\Tests;

use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;

class MultiLanguageWorkflowTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['HbReels\EventReelGenerator\EventReelServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('eventreel.ollama_url', 'http://localhost:11434');
        $app['config']->set('eventreel.ollama_model', 'mistral');
        $app['config']->set('eventreel.use_google_translate', true);
        $app['config']->set('filesystems.disks.local.root', '/tmp');
    }

    /**
     * Test the complete multi-language workflow from English input to Tamil output
     */
    public function test_complete_english_to_tamil_workflow()
    {
        // Create mock HTTP client for AI service
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();

        // Mock Ollama response for caption generation
        $captionResponse = ['response' => 'Coimbatore Food Festival Celebration'];
        $client->expects($this->once())
            ->method('post')
            ->with($this->stringContains('/api/generate'))
            ->willReturn(new Response(200, [], json_encode($captionResponse)));

        // Create AI service
        $aiService = new AIService($client);

        // Test 1: Generate English caption for video search
        $englishCaption = $aiService->generateCaption('Coimbatore City Food Festival Event', 'en');
        $this->assertEquals('Coimbatore Food Festival Celebration', $englishCaption);
        $this->assertIsString($englishCaption);

        // Test 2: Direct translation to Tamil
        $tamilCaption = $aiService->translateWithGoogle($englishCaption, 'ta', 'en');
        $this->assertIsString($tamilCaption);
        $this->assertNotEmpty($tamilCaption);
        // Note: Actual translation would happen with real Google Translate API

        // Test 3: Video renderer language detection
        $videoRenderer = new VideoRenderer();

        $reflection = new \ReflectionClass(VideoRenderer::class);

        // Test Tamil language detection
        $detectMethod = $reflection->getMethod('detectLanguage');
        $detectMethod->setAccessible(true);

        $detectedLang = $detectMethod->invoke($videoRenderer, 'கோவை உணவு விழா');
        $this->assertEquals('ta', $detectedLang);

        // Test font selection for Tamil
        $fontMethod = $reflection->getMethod('getFontForLanguage');
        $fontMethod->setAccessible(true);

        $tamilFont = $fontMethod->invoke($videoRenderer, 'ta');
        $this->assertStringContainsString('NotoSansTamil', $tamilFont);
        $this->assertStringContainsString('.ttf', $tamilFont);

        // Test ASS subtitle generation
        $assMethod = $reflection->getMethod('generateASSSubtitle');
        $assMethod->setAccessible(true);

        $tamilLines = ['கோவை நகரில்', 'உணவு விழா', 'நடைபெறும்'];
        $assContent = $assMethod->invoke($videoRenderer, $tamilLines, $tamilFont, 48, 800, 100, 1080, 1920);

        $this->assertStringStartsWith('[Script Info]', $assContent);
        $this->assertStringContainsString('Noto Sans Tamil', $assContent);
        $this->assertStringContainsString('கோவை நகரில்', $assContent);
        $this->assertStringContainsString('உணவு விழா', $assContent);
        $this->assertStringContainsString('நடைபெறும்', $assContent);

        // Verify ASS format structure
        $this->assertStringContainsString('[V4+ Styles]', $assContent);
        $this->assertStringContainsString('[Events]', $assContent);
        $this->assertStringContainsString('Dialogue:', $assContent);
    }

    /**
     * Test language detection for various scripts
     */
    public function test_language_detection_accuracy()
    {
        $videoRenderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('detectLanguage');
        $method->setAccessible(true);

        $testCases = [
            // Tamil
            ['கோவை நகரில் உணவு விழா நடைபெறும்', 'ta'],
            ['தமிழ் மொழி தேர்வு', 'ta'],

            // Hindi
            ['कोयंबटूर शहर में खाना उत्सव', 'hi'],
            ['हिंदी भाषा का चयन', 'hi'],

            // Telugu
            ['కోయంబటూరు నగరంలో ఆహారోత్సవం', 'te'],

            // Malayalam
            ['കോയമ്പത്തൂർ നഗരത്തിൽ ഭക്ഷ്യോത്സവം', 'ml'],

            // Kannada
            ['ಕೋಯಮ್ಬತ್ತೂರ್ ನಗರದಲ್ಲಿ ಆಹಾರೋತ್ಸವ', 'kn'],

            // Bengali
            ['কোয়েম্বাটোর শহরে খাদ্য উৎসব', 'bn'],

            // Gujarati
            ['કોયંબત્તૂર શહેરમાં ખાદ્ય મહોત્સવ', 'gu'],

            // Arabic
            ['سيتم تنظيم مهرجان الطعام في مدينة كويمباتور', 'ar'],

            // Persian
            ['جشن غذا در شهر کوییمباتور برگزار می‌شود', 'fa'],

            // Urdu
            ['کوئمبٹور شہر میں خوراک کا تہوار منعقد کیا جائے گا', 'ur'],

            // Thai
            ['เทศกาลอาหารในเมืองโคยัมบัตตูร์', 'th'],

            // Chinese
            ['在科imbatore市举办美食节', 'zh'],

            // Japanese
            ['コインバトール市でフードフェスティバル', 'ja'],

            // Korean
            ['코임바토르 시에서 음식 축제', 'ko'],

            // English
            ['Coimbatore City Food Festival Event', 'en'],
            ['English language selection test', 'en'],

            // Mixed content (should detect dominant)
            ['Hello கோவை world', 'en'], // English dominant
            ['கோவை Hello world', 'ta'], // Tamil dominant
        ];

        foreach ($testCases as [$text, $expectedLanguage]) {
            $detectedLanguage = $method->invoke($videoRenderer, $text);
            $this->assertEquals(
                $expectedLanguage,
                $detectedLanguage,
                "Failed to detect language for text: '$text'. Expected: $expectedLanguage, Got: $detectedLanguage"
            );
        }
    }

    /**
     * Test font selection for all supported languages
     */
    public function test_font_selection_for_all_languages()
    {
        $videoRenderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('getFontForLanguage');
        $method->setAccessible(true);

        $fontTests = [
            'ta' => 'NotoSansTamil-Regular.ttf',
            'hi' => 'NotoSansDevanagari-Regular.ttf',
            'te' => 'NotoSansTelugu-Regular.ttf',
            'ml' => 'NotoSansMalayalam-Regular.ttf',
            'kn' => 'NotoSansKannada-Regular.ttf',
            'bn' => 'NotoSansBengali-Regular.ttf',
            'gu' => 'NotoSansGujarati-Regular.ttf',
            'ar' => 'NotoSansArabic-Regular.ttf',
            'fa' => 'NotoSansArabic-Regular.ttf',
            'ur' => 'NotoSansArabic-Regular.ttf',
            'th' => 'NotoSansThai-Regular.ttf',
            'zh' => 'NotoSansCJK-Regular.ttc',
            'ja' => 'NotoSansCJK-Regular.ttc',
            'ko' => 'NotoSansCJK-Regular.ttc',
        ];

        foreach ($fontTests as $language => $expectedFont) {
            $fontPath = $method->invoke($videoRenderer, $language);

            $this->assertStringContainsString(
                $expectedFont,
                $fontPath,
                "Font selection failed for language $language. Expected to contain: $expectedFont"
            );

            $this->assertFileExists(
                $fontPath,
                "Font file does not exist: $fontPath for language $language"
            );
        }
    }

    /**
     * Test ASS subtitle rendering with complex scripts
     */
    public function test_ass_subtitle_rendering_with_complex_scripts()
    {
        $videoRenderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);

        $getFontMethod = $reflection->getMethod('getFontForLanguage');
        $getFontMethod->setAccessible(true);

        $assMethod = $reflection->getMethod('generateASSSubtitle');
        $assMethod->setAccessible(true);

        $fontFamilyMethod = $reflection->getMethod('getFontFamilyName');
        $fontFamilyMethod->setAccessible(true);

        // Test Tamil ASS generation
        $tamilFont = $getFontMethod->invoke($videoRenderer, 'ta');
        $tamilLines = ['கோவை நகரில்', 'உணவு விழா', 'நடைபெறும்'];
        $tamilASS = $assMethod->invoke($videoRenderer, $tamilLines, $tamilFont, 48, 800, 100, 1080, 1920);

        $this->assertStringContainsString('Noto Sans Tamil', $tamilASS);
        $this->assertStringContainsString('கோவை நகரில்', $tamilASS);
        $this->assertEquals(3, substr_count($tamilASS, 'Dialogue:'));

        // Test Arabic ASS generation (RTL)
        $arabicFont = $getFontMethod->invoke($videoRenderer, 'ar');
        $arabicLines = ['سيتم تنظيم', 'مهرجان الطعام', 'في المدينة'];
        $arabicASS = $assMethod->invoke($videoRenderer, $arabicLines, $arabicFont, 48, 800, 100, 1080, 1920);

        $this->assertStringContainsString('Noto Sans Arabic', $arabicASS);
        $this->assertStringContainsString('سيتم تنظيم', $arabicASS);
        $this->assertEquals(3, substr_count($arabicASS, 'Dialogue:'));

        // Test CJK ASS generation
        $cjkFont = $getFontMethod->invoke($videoRenderer, 'zh');
        $cjkLines = ['将在', '科imbatore市', '举办美食节'];
        $cjkASS = $assMethod->invoke($videoRenderer, $cjkLines, $cjkFont, 48, 800, 100, 1080, 1920);

        $this->assertStringContainsString('Noto Sans CJK JP', $cjkASS);
        $this->assertStringContainsString('将在', $cjkASS);
        $this->assertEquals(3, substr_count($cjkASS, 'Dialogue:'));

        // Verify ASS structure for all languages
        foreach ([$tamilASS, $arabicASS, $cjkASS] as $assContent) {
            $this->assertStringStartsWith('[Script Info]', $assContent);
            $this->assertStringContainsString('ScriptType: v4.00+', $assContent);
            $this->assertStringContainsString('[V4+ Styles]', $assContent);
            $this->assertStringContainsString('[Events]', $assContent);
            $this->assertStringContainsString('Format: Layer, Start, End, Style, Name', $assContent);
        }
    }

    /**
     * Test text wrapping functionality for different languages
     */
    public function test_text_wrapping_for_multilingual_content()
    {
        $videoRenderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('wrapText');
        $method->setAccessible(true);

        // Test Tamil text wrapping
        $longTamilText = 'கோவை நகரில் மிகப்பெரிய உணவு விழா நடைபெற உள்ளது இது மிகவும் சிறப்பான நிகழ்வாக இருக்கும்';
        $wrappedTamil = $method->invoke($videoRenderer, $longTamilText, 30);
        $this->assertStringContainsString('\\n', $wrappedTamil);

        // Test Arabic text wrapping
        $longArabicText = 'سيتم تنظيم مهرجان الطعام الكبير في مدينة كويمباتور وهذا سيكون حدثاً مميزاً جداً';
        $wrappedArabic = $method->invoke($videoRenderer, $longArabicText, 30);
        $this->assertStringContainsString('\\n', $wrappedArabic);

        // Test that short text doesn't get wrapped
        $shortText = 'கோவை நகரில்';
        $unwrappedText = $method->invoke($videoRenderer, $shortText, 50);
        $this->assertEquals($shortText, $unwrappedText);
        $this->assertStringNotContainsString('\\n', $unwrappedText);
    }

    /**
     * Test error handling and fallback scenarios
     */
    public function test_error_handling_and_fallbacks()
    {
        $videoRenderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);

        // Test invalid language font selection
        $fontMethod = $reflection->getMethod('getFontForLanguage');
        $fontMethod->setAccessible(true);

        $invalidLangFont = $fontMethod->invoke($videoRenderer, 'invalid_lang');
        $this->assertNull($invalidLangFont); // Should return null for invalid language

        // Test font family name for invalid font
        $familyMethod = $reflection->getMethod('getFontFamilyName');
        $familyMethod->setAccessible(true);

        $invalidFamily = $familyMethod->invoke($videoRenderer, '/nonexistent/font.ttf');
        $this->assertEquals('Font', $invalidFamily); // Should fallback

        $nullFamily = $familyMethod->invoke($videoRenderer, null);
        $this->assertEquals('Arial', $nullFamily); // Should use Arial fallback

        // Test language detection with empty text
        $detectMethod = $reflection->getMethod('detectLanguage');
        $detectMethod->setAccessible(true);

        $emptyLang = $detectMethod->invoke($videoRenderer, '');
        $this->assertEquals('en', $emptyLang);

        $nullLang = $detectMethod->invoke($videoRenderer, null);
        $this->assertEquals('en', $nullLang);
    }
}
