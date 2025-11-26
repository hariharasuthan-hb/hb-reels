<?php

namespace HbReels\EventReelGenerator\Tests;

use HbReels\EventReelGenerator\Services\VideoRenderer;
use Orchestra\Testbench\TestCase;

class VideoRendererTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['HbReels\EventReelGenerator\EventReelServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('eventreel.video.width', 1080);
        $app['config']->set('eventreel.video.height', 1920);
        $app['config']->set('eventreel.video.duration', 5);
        $app['config']->set('eventreel.video.fps', 30);
        $app['config']->set('filesystems.disks.local.root', '/tmp');
    }
    public function test_build_command_uses_escaped_paths()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('buildFFmpegCommand');
        $method->setAccessible(true);

        $command = $method->invoke(
            $renderer,
            '/usr/bin/ffmpeg',
            '/tmp/stock clip.mp4',
            '/tmp/output video.mp4',
            1080,
            1920,
            5,
            30,
            '/tmp/flyer.png',
            'Event Reel'
        );

        $this->assertStringContainsString("-i '/tmp/stock clip.mp4'", $command);
        $this->assertStringContainsString("-i '/tmp/flyer.png'", $command);
        $this->assertStringContainsString("-movflags +faststart '/tmp/output video.mp4'", $command);
        $this->assertStringContainsString('-map "[vout]"', $command);
    }

    public function test_detect_language_identifies_scripts()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('detectLanguage');
        $method->setAccessible(true);

        // Test English detection
        $result = $method->invoke($renderer, 'Hello world this is English text');
        $this->assertEquals('en', $result);

        // Test Tamil detection
        $result = $method->invoke($renderer, 'கோவை நகரில் உணவு விழா நடைபெறும்');
        $this->assertEquals('ta', $result);

        // Test Hindi detection
        $result = $method->invoke($renderer, 'कोयंबटूर शहर में खाना उत्सव आयोजित किया जाएगा');
        $this->assertEquals('hi', $result);

        // Test Arabic detection
        $result = $method->invoke($renderer, 'سيتم تنظيم مهرجان الطعام في مدينة كويمباتور');
        $this->assertEquals('ar', $result);

        // Test Chinese detection
        $result = $method->invoke($renderer, '将在科imbatore市举办美食节');
        $this->assertEquals('zh', $result);

        // Test mixed content (should detect dominant script)
        $result = $method->invoke($renderer, 'Hello கோவை world');
        $this->assertEquals('en', $result); // English dominant

        // Test empty text
        $result = $method->invoke($renderer, '');
        $this->assertEquals('en', $result);
    }

    public function test_get_font_for_language_returns_correct_paths()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('getFontForLanguage');
        $method->setAccessible(true);

        // Test Tamil font
        $fontPath = $method->invoke($renderer, 'ta');
        $this->assertStringContainsString('NotoSansTamil-Regular.ttf', $fontPath);

        // Test Hindi font
        $fontPath = $method->invoke($renderer, 'hi');
        $this->assertStringContainsString('NotoSansDevanagari-Regular.ttf', $fontPath);

        // Test Telugu font
        $fontPath = $method->invoke($renderer, 'te');
        $this->assertStringContainsString('NotoSansTelugu-Regular.ttf', $fontPath);

        // Test Arabic font
        $fontPath = $method->invoke($renderer, 'ar');
        $this->assertStringContainsString('NotoSansArabic-Regular.ttf', $fontPath);

        // Test CJK font
        $fontPath = $method->invoke($renderer, 'zh');
        $this->assertStringContainsString('NotoSansCJK-Regular.ttc', $fontPath);

        // Test Thai font
        $fontPath = $method->invoke($renderer, 'th');
        $this->assertStringContainsString('NotoSansThai-Regular.ttf', $fontPath);
    }

    public function test_get_font_family_name_maps_correctly()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('getFontFamilyName');
        $method->setAccessible(true);

        // Test Tamil font family mapping
        $fontFamily = $method->invoke($renderer, '/path/to/NotoSansTamil-Regular.ttf');
        $this->assertEquals('Noto Sans Tamil', $fontFamily);

        // Test Devanagari font family mapping
        $fontFamily = $method->invoke($renderer, '/path/to/NotoSansDevanagari-Regular.ttf');
        $this->assertEquals('Noto Sans Devanagari', $fontFamily);

        // Test Arabic font family mapping
        $fontFamily = $method->invoke($renderer, '/path/to/NotoSansArabic-Regular.ttf');
        $this->assertEquals('Noto Sans Arabic', $fontFamily);

        // Test CJK font family mapping
        $fontFamily = $method->invoke($renderer, '/path/to/NotoSansCJK-Regular.ttc');
        $this->assertEquals('Noto Sans CJK JP', $fontFamily);

        // Test unknown font (should fallback)
        $fontFamily = $method->invoke($renderer, '/path/to/UnknownFont.ttf');
        $this->assertEquals('Unknownfont', $fontFamily); // lowercase, no extension

        // Test null input
        $fontFamily = $method->invoke($renderer, null);
        $this->assertEquals('Arial', $fontFamily);
    }

    public function test_generate_ass_subtitle_creates_valid_format()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('generateASSSubtitle');
        $method->setAccessible(true);

        $lines = [
            'கோவை நகரில்',
            'உணவு விழா',
            'நடைபெறும்'
        ];

        $fontFile = '/tmp/NotoSansTamil-Regular.ttf';
        $result = $method->invoke($renderer, $lines, $fontFile, 48, 800, 100, 1080, 1920);

        // Check ASS format structure
        $this->assertStringStartsWith('[Script Info]', $result);
        $this->assertStringContainsString('[V4+ Styles]', $result);
        $this->assertStringContainsString('[Events]', $result);
        $this->assertStringContainsString('Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text', $result);

        // Check Tamil font is used
        $this->assertStringContainsString('Noto Sans Tamil', $result);

        // Check dialogue entries
        $this->assertStringContainsString('Dialogue:', $result);
        $this->assertStringContainsString('கோவை நகரில்', $result);
        $this->assertStringContainsString('உணவு விழா', $result);
        $this->assertStringContainsString('நடைபெறும்', $result);
    }

    public function test_build_command_handles_all_scenarios()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('buildFFmpegCommand');
        $method->setAccessible(true);

        // Test Scenario 1: Video only (no flyer, no caption)
        $command = $method->invoke(
            $renderer,
            'ffmpeg',
            '/tmp/video.mp4',
            '/tmp/output.mp4',
            1080,
            1920,
            5,
            30,
            null, // no flyer
            null  // no caption
        );

        $this->assertStringContainsString("-i '/tmp/video.mp4'", $command);
        $this->assertStringContainsString('[0:v]scale=1080:1920', $command);
        $this->assertStringContainsString('[v]trim=duration=5', $command);
        $this->assertStringNotContainsString('overlay', $command);
        $this->assertStringNotContainsString('ass=', $command);

        // Test Scenario 2: Video + Caption only
        $command = $method->invoke(
            $renderer,
            'ffmpeg',
            '/tmp/video.mp4',
            '/tmp/output.mp4',
            1080,
            1920,
            5,
            30,
            null, // no flyer
            'Test Caption' // caption
        );

        $this->assertStringContainsString('ass=', $command);
        $this->assertStringContainsString('[0:v]scale=1080:1920,ass=', $command);
        $this->assertStringNotContainsString('overlay', $command);
    }

    public function test_build_command_with_flyer_and_caption()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('buildFFmpegCommand');
        $method->setAccessible(true);

        // Test Scenario 4: Video + Flyer + Caption
        $command = $method->invoke(
            $renderer,
            'ffmpeg',
            '/tmp/video.mp4',
            '/tmp/output.mp4',
            1080,
            1920,
            5,
            30,
            '/tmp/flyer.jpg', // flyer
            'Test Caption' // caption
        );

        $this->assertStringContainsString("-i '/tmp/video.mp4'", $command);
        $this->assertStringContainsString("-i '/tmp/flyer.jpg'", $command);
        $this->assertStringContainsString('overlay=', $command);
        $this->assertStringContainsString('ass=', $command);
        $this->assertStringContainsString('[v0][flyer]overlay=', $command);
        $this->assertStringContainsString('ass=', $command); // ASS applied after overlay
    }

    public function test_wrap_text_handles_unicode_correctly()
    {
        $renderer = new VideoRenderer();
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $method = $reflection->getMethod('wrapText');
        $method->setAccessible(true);

        // Test Tamil text wrapping
        $longText = 'கோவை நகரில் உணவு விழா நடைபெறும் மற்றும் இது மிகவும் சிறப்பான நிகழ்வாக இருக்கும்';
        $result = $method->invoke($renderer, $longText, 20);

        $this->assertIsString($result);
        $this->assertStringContainsString('\\n', $result); // Should contain line breaks

        // Test short text (should not wrap)
        $shortText = 'கோவை நகரில்';
        $result = $method->invoke($renderer, $shortText, 20);
        $this->assertEquals($shortText, $result);
        $this->assertStringNotContainsString('\\n', $result);
    }

    public function test_complete_workflow_simulation()
    {
        // This test simulates the complete multi-language workflow
        $renderer = new VideoRenderer();

        // Test language detection
        $reflection = new \ReflectionClass(VideoRenderer::class);
        $detectMethod = $reflection->getMethod('detectLanguage');
        $detectMethod->setAccessible(true);

        $tamilText = 'கோவை நகரில் உணவு விழா நடைபெறும்';
        $detectedLang = $detectMethod->invoke($renderer, $tamilText);
        $this->assertEquals('ta', $detectedLang);

        // Test font selection for detected language
        $fontMethod = $reflection->getMethod('getFontForLanguage');
        $fontMethod->setAccessible(true);

        $fontPath = $fontMethod->invoke($renderer, 'ta');
        $this->assertStringContainsString('NotoSansTamil', $fontPath);

        // Test ASS subtitle generation
        $assMethod = $reflection->getMethod('generateASSSubtitle');
        $assMethod->setAccessible(true);

        $lines = ['கோவை நகரில்', 'உணவு விழா'];
        $assContent = $assMethod->invoke($renderer, $lines, $fontPath, 48, 800, 100, 1080, 1920);

        $this->assertStringContainsString('[Script Info]', $assContent);
        $this->assertStringContainsString('Noto Sans Tamil', $assContent);
        $this->assertStringContainsString('கோவை நகரில்', $assContent);

        // Verify the complete workflow works
        $this->assertNotEmpty($assContent);
        $this->assertGreaterThan(100, strlen($assContent)); // Should be substantial ASS content
    }
}
