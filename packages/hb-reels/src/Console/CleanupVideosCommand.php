<?php

namespace HbReels\EventReelGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupVideosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eventreel:cleanup-videos
                            {--age=3600 : Maximum age in seconds for files to keep (default: 1 hour, minimum: 2 minutes)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old video files from temp and output directories (respects 2-minute download delay)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxAge = max($this->option('age'), 120); // Respect 2-minute delay for downloads
        $dryRun = $this->option('dry-run');
        $disk = config('eventreel.storage.disk');
        $tempPath = config('eventreel.storage.temp_path');
        $outputPath = config('eventreel.storage.output_path');

        $this->info("🧹 Starting video cleanup...");
        $this->info("Storage disk: {$disk}");
        $this->info("Temp path: {$tempPath}");
        $this->info("Output path: {$outputPath}");
        $this->info("Max age: {$maxAge} seconds (" . round($maxAge/3600, 1) . " hours)");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No files will be deleted");
        }

        $now = time();
        $cleanedCount = 0;
        $totalSize = 0;

        try {
            // Clean temp files
            $this->info("\n📁 Cleaning temp directory...");
            $tempFiles = Storage::disk($disk)->files($tempPath);
            $tempCleaned = $this->processFiles($tempFiles, $disk, $maxAge, $now, $dryRun);
            $cleanedCount += $tempCleaned['count'];
            $totalSize += $tempCleaned['size'];

            // Clean output files
            $this->info("\n📁 Cleaning output directory...");
            $outputFiles = Storage::disk($disk)->files($outputPath);
            $outputCleaned = $this->processFiles($outputFiles, $disk, $maxAge, $now, $dryRun);
            $cleanedCount += $outputCleaned['count'];
            $totalSize += $outputCleaned['size'];

            if ($cleanedCount > 0) {
                $sizeFormatted = $this->formatBytes($totalSize);
                if ($dryRun) {
                    $this->info("✅ Would clean up {$cleanedCount} files ({$sizeFormatted})");
                } else {
                    $this->info("✅ Cleaned up {$cleanedCount} files ({$sizeFormatted})");
                    Log::info('Manual video cleanup completed', [
                        'files_cleaned' => $cleanedCount,
                        'space_freed_bytes' => $totalSize,
                        'temp_path' => $tempPath,
                        'output_path' => $outputPath
                    ]);
                }
            } else {
                $this->info("✨ No old files to clean up");
            }

        } catch (\Exception $e) {
            $this->error("❌ Cleanup failed: " . $e->getMessage());
            Log::error('Manual video cleanup failed', [
                'error' => $e->getMessage(),
                'temp_path' => $tempPath,
                'output_path' => $outputPath
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Process files for cleanup.
     */
    private function processFiles(array $files, string $disk, int $maxAge, int $now, bool $dryRun): array
    {
        $cleanedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $fullPath = Storage::disk($disk)->path($file);

            if (!file_exists($fullPath)) {
                continue;
            }

            $fileAge = $now - filemtime($fullPath);
            $fileSize = filesize($fullPath);

            if ($fileAge > $maxAge) {
                $ageFormatted = $this->formatAge($fileAge);
                $sizeFormatted = $this->formatBytes($fileSize);

                if ($dryRun) {
                    $this->line("  📄 Would delete: {$file} ({$ageFormatted} old, {$sizeFormatted})");
                } else {
                    try {
                        Storage::disk($disk)->delete($file);
                        $this->line("  🗑️  Deleted: {$file} ({$ageFormatted} old, {$sizeFormatted})");
                        $cleanedCount++;
                        $totalSize += $fileSize;
                    } catch (\Exception $e) {
                        $this->error("  ❌ Failed to delete: {$file} - " . $e->getMessage());
                    }
                }
            }
        }

        return ['count' => $cleanedCount, 'size' => $totalSize];
    }

    /**
     * Format bytes into human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    /**
     * Format age in seconds into human readable format.
     */
    private function formatAge(int $seconds): string
    {
        if ($seconds < 60) return "{$seconds}s";
        if ($seconds < 3600) return round($seconds/60, 1) . "m";
        if ($seconds < 86400) return round($seconds/3600, 1) . "h";
        return round($seconds/86400, 1) . "d";
    }
}

