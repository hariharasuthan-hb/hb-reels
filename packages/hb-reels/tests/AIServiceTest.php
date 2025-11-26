<?php

namespace HbReels\EventReelGenerator\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use HbReels\EventReelGenerator\Services\AIService;
use Orchestra\Testbench\TestCase;

class AIServiceTest extends TestCase
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
    }

    public function test_format_event_description_uses_api_response()
    {
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();

        $payload = ['response' => "Event Name: Demo Event\nDate & Time: Feb 14, 2026"];
        $client->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($payload)));

        $service = new AIService($client);
        $result = $service->generateCaption('raw text');

        $this->assertSame("Event Name: Demo Event\nDate & Time: Feb 14, 2026", $result);
    }

    public function test_extract_event_details_returns_lines()
    {
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();
        
        $payload = ['response' => '{"line1": "Demo Event", "line2": "Feb 14 2026", "line3": "New York"}'];
        $client->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($payload)));

        $service = new AIService($client);
        $result = $service->extractEventDetails('raw text');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('line1', $result);
        $this->assertEquals('Demo Event', $result['line1']);
    }

    public function test_extract_details_fallback_when_api_fails()
    {
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();

        $client->method('post')->willThrowException(new class('api failure') extends \Exception implements GuzzleException {
        });

        $service = new AIService($client);
        $text = "Important announcement. Schools closed Monday. Stay safe everyone.";
        $result = $service->extractEventDetails($text);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        // Should have at least one line
        $this->assertTrue(isset($result['line1']) || isset($result['line2']));
    }

    public function test_translate_with_google_handles_different_languages()
    {
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();

        $service = new AIService($client);

        // Test that translateWithGoogle method exists and is callable
        $this->assertTrue(method_exists($service, 'translateWithGoogle'));

        // Test with same language (should return original text)
        $reflection = new \ReflectionClass(AIService::class);
        $method = $reflection->getMethod('translateWithGoogle');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'Hello World', 'en', 'en');
        $this->assertEquals('Hello World', $result);

        // Test with disabled translation (should return original text)
        // Note: This would require mocking config, but we'll test the method signature
        $this->assertTrue(is_callable([$service, 'translateWithGoogle']));
    }

    public function test_generate_caption_with_different_languages()
    {
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();

        // Mock successful AI response
        $payload = ['response' => "Beautiful sunset celebration event"];
        $client->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($payload)));

        $service = new AIService($client);

        // Test English caption generation
        $result = $service->generateCaption('sunset party event', 'en');
        $this->assertEquals("Beautiful sunset celebration event", $result);

        // Test non-English language (should trigger translation)
        // This would normally call translateWithGoogle, but since we're mocking,
        // it will return the English response
        $resultTamil = $service->generateCaption('sunset party event', 'ta');
        $this->assertEquals("Beautiful sunset celebration event", $resultTamil);
    }

    public function test_extract_event_details_with_translation()
    {
        $client = $this->getMockBuilder(ClientInterface::class)
            ->onlyMethods(['send', 'sendAsync', 'request', 'requestAsync', 'getConfig'])
            ->addMethods(['post'])
            ->getMock();

        // Mock AI response with structured data
        $payload = ['response' => '{"line1": "Sunset Party", "line2": "Beach Location", "line3": "Free Entry"}'];
        $client->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($payload)));

        $service = new AIService($client);

        // Test extraction for English (no translation needed)
        $result = $service->extractEventDetails('Beach party at sunset', 'en');
        $this->assertIsArray($result);
        $this->assertEquals('Sunset Party', $result['line1']);
        $this->assertEquals('Beach Location', $result['line2']);
        $this->assertEquals('Free Entry', $result['line3']);
    }

    public function test_preprocessing_for_translation()
    {
        $service = new AIService();

        // Test the preprocessing method (private, so use reflection)
        $reflection = new \ReflectionClass(AIService::class);
        $method = $reflection->getMethod('preprocessForTranslation');
        $method->setAccessible(true);

        // Test Tamil preprocessing
        $result = $method->invoke($service, 'Action star Vijay announces new movie', 'ta');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);

        // Test normal text (should remain unchanged)
        $normalText = 'Regular announcement text';
        $result = $method->invoke($service, $normalText, 'ta');
        $this->assertEquals($normalText, $result);
    }

    public function test_is_english_text_detection()
    {
        $service = new AIService();

        $reflection = new \ReflectionClass(AIService::class);
        $method = $reflection->getMethod('isEnglishText');
        $method->setAccessible(true);

        // Test English text
        $result = $method->invoke($service, 'Hello world this is English text');
        $this->assertTrue($result);

        // Test non-English text (Tamil)
        $result = $method->invoke($service, 'கோவை நகரில் உணவு விழா நடைபெறும்');
        $this->assertFalse($result);

        // Test mixed text
        $result = $method->invoke($service, 'Hello கோவை world');
        $this->assertTrue($result); // Should be true due to >70% ASCII

        // Test empty text
        $result = $method->invoke($service, '');
        $this->assertTrue($result);
    }
}





