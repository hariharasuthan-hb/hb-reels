<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class VideoConversionService
{
    /**
     * Convert video to web-compatible H.264 MP4 format.
     * 
     * @param UploadedFile|string $videoFile The uploaded file or path to video file
     * @param string $outputPath The storage path where converted video should be saved
     * @param array $options Conversion options (quality, resolution, etc.)
     * @return string|null Path to converted video file, or null on failure
     */
    public function convertToWebFormat($videoFile, string $outputPath, array $options = []): ?string
    {
        try {
            Log::info('VideoConversionService: Starting conversion', [
                'output_path' => $outputPath,
                'has_file' => $videoFile instanceof UploadedFile,
            ]);
            
            // Determine input file path
            $inputPath = $this->getInputFilePath($videoFile);
            if (!$inputPath || !file_exists($inputPath)) {
                Log::error('VideoConversionService: Input file not found', [
                    'path' => $inputPath,
                    'file_exists' => $inputPath ? file_exists($inputPath) : false,
                ]);
                // Fallback to storing original
                return $this->storeOriginalFile($videoFile, $outputPath);
            }
            
            Log::info('VideoConversionService: Input file found', [
                'input_path' => $inputPath,
                'file_size' => filesize($inputPath),
            ]);

            // Check if FFmpeg is available
            $ffmpegAvailable = $this->isFFmpegAvailable();
            Log::info('VideoConversionService: FFmpeg check', [
                'available' => $ffmpegAvailable,
                'path' => $this->getFFmpegPath(),
            ]);
            
            if (!$ffmpegAvailable) {
                Log::warning('VideoConversionService: FFmpeg not available, storing original file');
                return $this->storeOriginalFile($videoFile, $outputPath);
            }

            // Prepare output path - ensure .mp4 extension
            $outputPath = preg_replace('/\.[^.]+$/', '.mp4', $outputPath);
            $outputDir = dirname(storage_path('app/public/' . $outputPath));
            if (!File::exists($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            $fullOutputPath = storage_path('app/public/' . $outputPath);

            // Conversion options
            $quality = $options['quality'] ?? config('video.conversion.quality', 'medium');
            $maxResolution = $options['max_resolution'] ?? config('video.conversion.max_resolution', '1920x1080');
            $bitrate = $options['bitrate'] ?? $this->getBitrateForQuality($quality);
            $audioBitrate = $options['audio_bitrate'] ?? config('video.conversion.audio_bitrate', '128k');

            // Build FFmpeg command
            $command = $this->buildFFmpegCommand(
                $inputPath,
                $fullOutputPath,
                $maxResolution,
                $bitrate,
                $audioBitrate
            );

            // Execute conversion
            Log::info('VideoConversionService: Executing FFmpeg command', [
                'command' => $command,
            ]);
            
            $output = [];
            $returnVar = 0;
            $startTime = microtime(true);
            exec($command . ' 2>&1', $output, $returnVar);
            $duration = microtime(true) - $startTime;

            Log::info('VideoConversionService: FFmpeg execution completed', [
                'return_var' => $returnVar,
                'duration' => round($duration, 2) . 's',
                'output_exists' => file_exists($fullOutputPath),
                'output_size' => file_exists($fullOutputPath) ? filesize($fullOutputPath) : 0,
            ]);

            if ($returnVar !== 0 || !file_exists($fullOutputPath)) {
                Log::error('VideoConversionService: Conversion failed', [
                    'command' => $command,
                    'output' => implode("\n", array_slice($output, -20)), // Last 20 lines
                    'return_var' => $returnVar,
                    'output_exists' => file_exists($fullOutputPath),
                ]);
                
                // Fallback to storing original file
                return $this->storeOriginalFile($videoFile, $outputPath);
            }

            // Verify the converted file is valid
            if (filesize($fullOutputPath) === 0) {
                Log::error('VideoConversionService: Converted file is empty');
                return $this->storeOriginalFile($videoFile, $outputPath);
            }

            // Clean up temporary file if it was created from UploadedFile
            if ($videoFile instanceof UploadedFile && $inputPath !== $videoFile->getRealPath()) {
                @unlink($inputPath);
            }

            Log::info('VideoConversionService: Video converted successfully', [
                'input' => $inputPath,
                'output' => $fullOutputPath,
                'size' => filesize($fullOutputPath),
            ]);

            return $outputPath;

        } catch (\Exception $e) {
            Log::error('VideoConversionService: Exception during conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to storing original file
            return $this->storeOriginalFile($videoFile, $outputPath);
        }
    }

    /**
     * Get input file path from UploadedFile or string path.
     */
    protected function getInputFilePath($videoFile): ?string
    {
        if ($videoFile instanceof UploadedFile) {
            return $videoFile->getRealPath();
        }

        if (is_string($videoFile)) {
            // Check if it's a storage path
            if (Storage::disk('public')->exists($videoFile)) {
                return Storage::disk('public')->path($videoFile);
            }
            
            // Check if it's an absolute path
            if (file_exists($videoFile)) {
                return $videoFile;
            }
        }

        return null;
    }

    /**
     * Store original file without conversion (fallback).
     */
    protected function storeOriginalFile($videoFile, string $outputPath): ?string
    {
        try {
            Log::info('VideoConversionService: Storing original file (no conversion)', [
                'output_path' => $outputPath,
            ]);
            
            if ($videoFile instanceof UploadedFile) {
                $extension = $videoFile->getClientOriginalExtension();
                $fileName = pathinfo($outputPath, PATHINFO_FILENAME);
                $outputPath = dirname($outputPath) . '/' . $fileName . '.' . $extension;
                
                $storedPath = $videoFile->storeAs(dirname($outputPath), basename($outputPath), 'public');
                Log::info('VideoConversionService: Original file stored', ['path' => $storedPath]);
                return $storedPath;
            }

            if (is_string($videoFile) && file_exists($videoFile)) {
                $extension = pathinfo($videoFile, PATHINFO_EXTENSION);
                $fileName = pathinfo($outputPath, PATHINFO_FILENAME);
                $outputPath = dirname($outputPath) . '/' . $fileName . '.' . $extension;
                
                $outputDir = dirname(storage_path('app/public/' . $outputPath));
                if (!File::exists($outputDir)) {
                    File::makeDirectory($outputDir, 0755, true);
                }
                
                copy($videoFile, storage_path('app/public/' . $outputPath));
                Log::info('VideoConversionService: Original file copied', ['path' => $outputPath]);
                return $outputPath;
            }

            Log::error('VideoConversionService: Cannot store original file - invalid input');
            return null;
        } catch (\Exception $e) {
            Log::error('VideoConversionService: Failed to store original file', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if FFmpeg is available on the system.
     */
    protected function isFFmpegAvailable(): bool
    {
        $ffmpegPath = $this->getFFmpegPath();
        
        $output = [];
        $returnVar = 0;
        exec($ffmpegPath . ' -version 2>&1', $output, $returnVar);
        
        return $returnVar === 0;
    }

    /**
     * Get FFmpeg executable path.
     */
    protected function getFFmpegPath(): string
    {
        // Check config first
        $configPath = config('video.ffmpeg_path');
        if ($configPath && file_exists($configPath)) {
            return $configPath;
        }

        // Try common paths
        $commonPaths = [
            'ffmpeg', // In PATH
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'C:\\ffmpeg\\bin\\ffmpeg.exe', // Windows
        ];

        foreach ($commonPaths as $path) {
            $output = [];
            $returnVar = 0;
            exec(($path === 'ffmpeg' ? $path : escapeshellarg($path)) . ' -version 2>&1', $output, $returnVar);
            if ($returnVar === 0) {
                return $path;
            }
        }

        return 'ffmpeg'; // Default, will fail if not in PATH
    }

    /**
     * Build FFmpeg command for conversion.
     */
    protected function buildFFmpegCommand(
        string $inputPath,
        string $outputPath,
        string $maxResolution,
        string $bitrate,
        string $audioBitrate
    ): string {
        $ffmpegPath = $this->getFFmpegPath();
        
        // Escape paths
        $inputPathEscaped = escapeshellarg($inputPath);
        $outputPathEscaped = escapeshellarg($outputPath);

        // Parse max resolution
        list($maxWidth, $maxHeight) = explode('x', $maxResolution);
        
        // Simplified FFmpeg command for maximum compatibility
        // -c:v libx264: H.264 video codec (most compatible)
        // -preset medium: Balance between speed and quality
        // -crf 23: Good quality (18-28 range, lower = better)
        // -c:a aac: AAC audio codec (most compatible)
        // -pix_fmt yuv420p: Pixel format (required for compatibility)
        // -movflags +faststart: Enable web streaming
        // -vf scale: Scale if needed (simpler syntax)
        
        // Use simpler scale filter that works on Windows
        // Scale only if video is larger than max resolution
        $scaleFilter = sprintf('scale=if(gt(iw\\,%s)\\,%s\\,iw):if(gt(ih\\,%s)\\,%s\\,ih)', 
            $maxWidth, $maxWidth, $maxHeight, $maxHeight);
        
        // Build command - use simpler approach for Windows
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: Use simpler command without complex filters
            $command = sprintf(
                '%s -i %s -c:v libx264 -preset medium -crf 23 -maxrate %s -bufsize %s -vf scale=%s:%s:force_original_aspect_ratio=decrease -c:a aac -b:a %s -pix_fmt yuv420p -movflags +faststart -y %s',
                escapeshellcmd($ffmpegPath),
                $inputPathEscaped,
                $bitrate,
                $this->getBufferSize($bitrate),
                $maxWidth,
                $maxHeight,
                $audioBitrate,
                $outputPathEscaped
            );
        } else {
            // Linux/Mac: Can use more complex filters
            $command = sprintf(
                '%s -i %s -c:v libx264 -preset medium -crf 23 -maxrate %s -bufsize %s -vf "scale=\'min(%s,iw)\':\'min(%s,ih)\':force_original_aspect_ratio=decrease" -c:a aac -b:a %s -pix_fmt yuv420p -movflags +faststart -y %s',
                escapeshellcmd($ffmpegPath),
                $inputPathEscaped,
                $bitrate,
                $this->getBufferSize($bitrate),
                $maxWidth,
                $maxHeight,
                $audioBitrate,
                $outputPathEscaped
            );
        }

        return $command;
    }

    /**
     * Get bitrate based on quality setting.
     */
    protected function getBitrateForQuality(string $quality): string
    {
        return match($quality) {
            'low' => '1M',
            'medium' => '2M',
            'high' => '5M',
            default => '2M',
        };
    }

    /**
     * Get buffer size based on bitrate.
     */
    protected function getBufferSize(string $bitrate): string
    {
        // Buffer size should be 2x the bitrate
        $bitrateValue = (int) filter_var($bitrate, FILTER_SANITIZE_NUMBER_INT);
        $multiplier = 2;
        
        if (str_contains($bitrate, 'M')) {
            return ($bitrateValue * $multiplier) . 'M';
        } elseif (str_contains($bitrate, 'k')) {
            return ($bitrateValue * $multiplier) . 'k';
        }
        
        return '4M'; // Default
    }

    /**
     * Get video duration in seconds.
     */
    public function getVideoDuration($videoFile): ?int
    {
        if (!$this->isFFmpegAvailable()) {
            return null;
        }

        $inputPath = $this->getInputFilePath($videoFile);
        if (!$inputPath || !file_exists($inputPath)) {
            return null;
        }

        $ffprobePath = str_replace('ffmpeg', 'ffprobe', $this->getFFmpegPath());
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellcmd($ffprobePath),
            escapeshellarg($inputPath)
        );

        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar === 0 && !empty($output[0])) {
            return (int) round((float) $output[0]);
        }

        return null;
    }
}
