<?php

namespace HbReels\EventReelGenerator\Tests;

use HbReels\EventReelGenerator\Services\VideoRenderer;
use PHPUnit\Framework\TestCase;

class VideoRendererTest extends TestCase
{
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
}
<?php

namespace HbReels\EventReelGenerator\Tests;

use HbReels\EventReelGenerator\Services\VideoRenderer;
use PHPUnit\Framework\TestCase;

class VideoRendererTest extends TestCase
{
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
}
