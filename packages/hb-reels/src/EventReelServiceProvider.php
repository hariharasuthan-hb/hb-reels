<?php

namespace HbReels\EventReelGenerator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use HbReels\EventReelGenerator\Console\GenerateReelCommand;
use HbReels\EventReelGenerator\Console\CleanupVideosCommand;

class EventReelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/eventreel.php',
            'eventreel'
        );

        // Register services as singletons
        $this->app->singleton(\HbReels\EventReelGenerator\Services\OCRService::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\AIService::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\PexelsService::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\VideoRenderer::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\GrammarService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/eventreel.php' => config_path('eventreel.php'),
        ], 'eventreel-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/eventreel'),
        ], 'eventreel-views');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'eventreel');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateReelCommand::class,
                CleanupVideosCommand::class,
            ]);
        }

        // Load routes
        $this->loadRoutes();

        // Schedule cleanup of old video files
        $this->scheduleCleanup();
    }

    /**
     * Load package routes.
     * Routes require authentication and active subscription (unless user is admin).
     */
    protected function loadRoutes(): void
    {
        Route::middleware(['web', 'auth', 'check.subscription'])
            ->prefix(config('eventreel.route_prefix', 'event-reel'))
            ->name(config('eventreel.route_name_prefix', 'eventreel.'))
            ->group(function () {
                require __DIR__ . '/../routes/web.php';
            });
    }

    /**
     * Schedule cleanup of old video files to prevent disk space accumulation.
     */
    protected function scheduleCleanup(): void
    {
        // Only schedule if we're not in console (to avoid duplicate scheduling)
        if (!$this->app->runningInConsole()) {
            return;
        }

        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

        // Clean up temp and output video files older than 1 hour
        $schedule->call(function () {
            $this->cleanupOldVideoFiles();
        })->hourly();
    }

    /**
     * Clean up old video files from temp and output directories and orphaned database records.
     */
    protected function cleanupOldVideoFiles(): void
    {
        $disk = config('eventreel.storage.disk');
        $tempPath = config('eventreel.storage.temp_path');
        $outputPath = config('eventreel.storage.output_path');

        // Maximum age for files (1 hour = 3600 seconds)
        $maxAge = 3600;
        $now = time();
        $filesCleaned = 0;
        $recordsCleaned = 0;

        try {
            // Clean temp files
            $tempFiles = Storage::disk($disk)->files($tempPath);
            foreach ($tempFiles as $file) {
                $fullPath = Storage::disk($disk)->path($file);
                if (file_exists($fullPath) && ($now - filemtime($fullPath)) > $maxAge) {
                    Storage::disk($disk)->delete($file);
                    $filesCleaned++;
                }
            }

            // Clean output files and check for orphaned database records
            $outputFiles = Storage::disk($disk)->files($outputPath);
            foreach ($outputFiles as $file) {
                $fullPath = Storage::disk($disk)->path($file);
                if (file_exists($fullPath) && ($now - filemtime($fullPath)) > $maxAge) {
                    Storage::disk($disk)->delete($file);
                    $filesCleaned++;
                }
            }

            // Clean up orphaned database records (completed videos with missing files)
            $oldCompletedVideos = \App\Models\ActivityLog::where('activity_type', 'event_reel_generation')
                ->where('status', 'completed')
                ->where('created_at', '<', now()->subHours(2)) // Older than 2 hours
                ->whereNotNull('video_path')
                ->get();

            foreach ($oldCompletedVideos as $video) {
                if (!Storage::disk($disk)->exists($video->video_path)) {
                    // File doesn't exist, clean up the database record
                    $video->delete();
                    $recordsCleaned++;
                }
            }

            if ($filesCleaned > 0 || $recordsCleaned > 0) {
                \Log::info('Cleaned up old video files and records', [
                    'files_cleaned' => $filesCleaned,
                    'records_cleaned' => $recordsCleaned,
                    'temp_path' => $tempPath,
                    'output_path' => $outputPath
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to cleanup old video files and records', [
                'error' => $e->getMessage(),
                'temp_path' => $tempPath,
                'output_path' => $outputPath
            ]);
        }
    }
}

