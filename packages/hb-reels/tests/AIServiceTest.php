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
        $client = $this->createMock(ClientInterface::class);
        $payload = ['response' => "Event Name: Demo Event\nDate & Time: Feb 14, 2026"];
        $client->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($payload)));

        $service = new AIService($client);
        $result = $service->formatEventDescription('raw text');

        $this->assertSame("Event Name: Demo Event\nDate & Time: Feb 14, 2026", $result);
    }

    public function test_format_event_description_sanitized_when_api_fails()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('post')->willThrowException(new class('api failure') extends \Exception implements GuzzleException {
        });

        $service = new AIService($client);
        $rawEvent = "Demo Event\nFeb 14 2026\nNew York\nOpening keynote\nNetworking";
        $result = $service->formatEventDescription($rawEvent);

        $this->assertStringContainsString('Event Name:', $result);
        $this->assertStringContainsString('Date & Time:', $result);
        $this->assertStringContainsString('Location:', $result);
        $this->assertStringContainsString('Highlights:', $result);
        $this->assertStringContainsString('Call to Action:', $result);
    }
}


