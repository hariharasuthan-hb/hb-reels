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
            // First, normalize literal \n to actual newlines
            $caption = str_replace('\\n', "\n", $caption);
            
            // Split caption into separate lines by any newline format
            $lines = preg_split('/\r\n|\r|\n/', $caption, -1, PREG_SPLIT_NO_EMPTY);
            
            // Get font file - try config first, then fall back to system defaults
            $fontFile = config('eventreel.video.font_path');
            
            if (!$fontFile || !file_exists($fontFile)) {
                $possibleFonts = [
                    '/System/Library/Fonts/Supplemental/Arial Bold.ttf',  // macOS
                    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',  // Linux
                    'C:\Windows\Fonts\arialbd.ttf',  // Windows
                    storage_path('fonts/Arial-Bold.ttf'),  // Custom storage location
                ];
                
                foreach ($possibleFonts as $font) {
                    if (file_exists($font)) {
                        $fontFile = $font;
                        break;
                    }
                }
            }
            
            // Calculate starting Y position to center the text block vertically
            $lineCount = count($lines);
            $yStep = 80;  // Space between each line
            
            // Calculate total height needed for all lines
            $totalTextHeight = ($lineCount * $yStep);
            
            // Center vertically: (video height - total text height) / 2
            $yStart = ($height - $totalTextHeight) / 2;
            $currentY = $yStart;
            
            $lineIndex = 0;
            
            foreach ($lines as $line) {
                // Skip empty lines
                if (trim($line) === '') {
                    continue;
                }
                
                // Escape special characters for FFmpeg
                $safe = str_replace("'", "", $line);
                $safe = str_replace(':', '\\:', $safe);
                
                // Create unique stream labels for each line to chain them properly
                $inputLabel = $lineIndex === 0 ? '[v]' : "[v{$lineIndex}]";
                $outputLabel = "[v" . ($lineIndex + 1) . "]";
                
                // Draw text WITHOUT individual box (box=0)
                if ($fontFile && file_exists($fontFile)) {
                    $filters[] = sprintf(
                        "%sdrawtext=fontfile=%s:text='%s':fontsize=48:fontcolor=white:" .
                        "x=(w-text_w)/2:y=%d%s",
                        $inputLabel,
                        escapeshellarg($fontFile),
                        $safe,
                        $currentY,
                        $outputLabel
                    );
                } else {
                    $filters[] = sprintf(
                        "%sdrawtext=text='%s':fontsize=48:fontcolor=white:" .
                        "x=(w-text_w)/2:y=%d%s",
                        $inputLabel,
                        $safe,
                        $currentY,
                        $outputLabel
                    );
                }
                
                $currentY += $yStep;
                $lineIndex++;
            }
            
            // Add ONE overlay box covering all text lines
            if ($lineIndex > 0) {
                $finalTextLabel = "[v{$lineIndex}]";
                
                // Calculate box dimensions
                $boxX = 40;  // Left padding from edge
                $boxY = $yStart - 40;  // Top of first line minus padding
                $boxWidth = $width - 80;  // Full width minus left/right padding
                $boxHeight = $totalTextHeight + 80;  // All lines plus top/bottom padding
                
                // Draw a semi-transparent black rectangle as background for all text
                $filters[] = sprintf(
                    "%sdrawbox=x=%d:y=%d:w=%d:h=%d:color=black@0.65:t=fill[v]",
                    $finalTextLabel,
                    $boxX,
                    $boxY,
                    $boxWidth,
                    $boxHeight
                );
            }
        }

        // Trim video to exact duration and set FPS
        $filters[] = '[v]trim=duration=' . $duration . ',setpts=PTS-STARTPTS,fps=' . $fps . '[vout]';
        
        // Join all filters into a single string for filter_complex
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

