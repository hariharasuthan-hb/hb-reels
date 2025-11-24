<?php

namespace HbReels\EventReelGenerator\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use HbReels\EventReelGenerator\Services\AIService;
use PHPUnit\Framework\TestCase;

class AIServiceTest extends TestCase
{
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
}





