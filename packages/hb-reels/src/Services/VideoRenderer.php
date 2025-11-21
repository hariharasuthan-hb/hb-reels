<?php

namespace HbReels\EventReelGenerator\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoRenderer
{
    /**
     * Render final video with flyer overlay and/or caption.
     */
    public function render(
        string $stockVideoPath,
        ?string $flyerPath = null,
        ?string $caption = null
    ): string {
        $disk = config('eventreel.storage.disk');
        $ffmpegPath = config('eventreel.ffmpeg.path', 'ffmpeg');
        
        $width = config('eventreel.video.width', 1080);
        $height = config('eventreel.video.height', 1920);
        $duration = config('eventreel.video.duration', 5);
        $fps = config('eventreel.video.fps', 30);

        // Get full paths
        $stockVideoFullPath = Storage::disk($disk)->path($stockVideoPath);
        $outputPath = config('eventreel.storage.output_path') . '/' . Str::random(40) . '.mp4';
        $outputFullPath = Storage::disk($disk)->path($outputPath);

        // Ensure output directory exists
        $outputDir = dirname($outputFullPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Build FFmpeg command
        $command = $this->buildFFmpegCommand(
            $ffmpegPath,
            $stockVideoFullPath,
            $outputFullPath,
            $width,
            $height,
            $duration,
            $fps,
            $flyerPath ? Storage::disk($disk)->path($flyerPath) : null,
            $caption
        );

        // Execute FFmpeg
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Video rendering failed: ' . implode("\n", $output));
        }

        return $outputPath;
    }

    /**
     * Build FFmpeg command based on rendering mode.
     */
    private function buildFFmpegCommand(
        string $ffmpegPath,
        string $stockVideoPath,
        string $outputPath,
        int $width,
        int $height,
        int $duration,
        int $fps,
        ?string $flyerPath = null,
        ?string $caption = null
    ): string {
        $filters = [];
        $inputs = [];

        // Input 1: Stock video
        $inputs[] = sprintf('-i %s', escapeshellarg($stockVideoPath));

        // Input 2: Flyer (if provided)
        if ($flyerPath) {
            $inputs[] = sprintf('-i %s', escapeshellarg($flyerPath));
            
            // Scale and overlay flyer
            $filters[] = sprintf(
                '[0:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1[v0]',
                $width,
                $height,
                $width,
                $height
            );
            $filters[] = sprintf(
                '[1:v]scale=%d:-1[flyer]',
                intval($width * 0.8)
            );
            $filters[] = '[v0][flyer]overlay=(W-w)/2:(H-h)/2[v]';
        } else {
            // Just scale stock video
            $filters[] = sprintf(
                '[0:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1[v]',
                $width,
                $height,
                $width,
                $height
            );
        }

        // Add caption text overlay if provided and flyer is not shown
        if ($caption && !$flyerPath) {
            // Escape special characters for FFmpeg
            $captionEscaped = str_replace([':', '\\'], ['\\:', '\\\\'], $caption);
            $captionEscaped = escapeshellarg($captionEscaped);
            
            $filters[] = sprintf(
                '[v]drawtext=text=%s:fontsize=48:fontcolor=white:x=(w-text_w)/2:y=h-th-100:box=1:boxcolor=black@0.5:boxborderw=10[v]',
                $captionEscaped
            );
        }

        // Trim video to exact duration and set FPS
        $filters[] = '[v]trim=duration=' . $duration . ',setpts=PTS-STARTPTS,fps=' . $fps . '[vout]';

        $filterComplex = implode(';', $filters);

        // Build final command
        $command = sprintf(
            '%s %s -filter_complex "%s" -map "[vout]" -t %d -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -movflags +faststart %s',
            escapeshellarg($ffmpegPath),
            implode(' ', $inputs),
            $filterComplex,
            $duration,
            escapeshellarg($outputPath)
        );

        return $command;
    }
}

