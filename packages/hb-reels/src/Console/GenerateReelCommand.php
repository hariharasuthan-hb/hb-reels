<?php

namespace HbReels\EventReelGenerator\Console;

use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateReelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eventreel:generate 
                            {--flyer= : Path to flyer image file}
                            {--text= : Event text description}
                            {--show-flyer : Show flyer in video}
                            {--output= : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an event reel from a flyer image or text';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $flyerPath = $this->option('flyer');
        $eventText = $this->option('text');
        $showFlyer = $this->option('show-flyer');
        $outputPath = $this->option('output');

        if (!$flyerPath && !$eventText) {
            $this->error('Either --flyer or --text option must be provided.');
            return Command::FAILURE;
        }

        try {
            $this->info('Generating event reel...');

            $ocrService = app(OCRService::class);
            $aiService = app(AIService::class);
            $pexelsService = app(PexelsService::class);
            $videoRenderer = app(VideoRenderer::class);

            // Extract text
            if ($flyerPath) {
                if (!file_exists($flyerPath)) {
                    $this->error("Flyer file not found: {$flyerPath}");
                    return Command::FAILURE;
                }
                $this->info('Extracting text from flyer...');
                $eventText = $ocrService->extractText($flyerPath);
                $this->info("Extracted text: {$eventText}");
            }

            // Generate caption
            $this->info('Generating AI caption...');
            $caption = $aiService->generateCaption($eventText);
            $this->info("Caption: {$caption}");

            // Get stock video
            $this->info('Fetching stock video from Pexels...');
            $stockVideoPath = $pexelsService->downloadVideo($caption);

            // Store flyer if provided
            $storedFlyerPath = null;
            if ($flyerPath && $showFlyer) {
                $disk = config('eventreel.storage.disk');
                $storedFlyerPath = config('eventreel.storage.temp_path') . '/' . basename($flyerPath);
                Storage::disk($disk)->put($storedFlyerPath, file_get_contents($flyerPath));
            }

            // Render video
            $this->info('Rendering video...');
            $finalPath = $videoRenderer->render(
                stockVideoPath: $stockVideoPath,
                flyerPath: $storedFlyerPath,
                caption: $showFlyer ? null : $caption
            );

            // Move to output path if specified
            if ($outputPath) {
                $disk = config('eventreel.storage.disk');
                $fullOutputPath = Storage::disk($disk)->path($finalPath);
                if (copy($fullOutputPath, $outputPath)) {
                    $this->info("Video saved to: {$outputPath}");
                } else {
                    $this->warn("Could not copy to {$outputPath}, video is at: {$fullOutputPath}");
                }
            } else {
                $disk = config('eventreel.storage.disk');
                $fullPath = Storage::disk($disk)->path($finalPath);
                $this->info("Video generated: {$fullPath}");
            }

            // Cleanup
            if ($storedFlyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($storedFlyerPath);
            }
            Storage::disk(config('eventreel.storage.disk'))->delete($stockVideoPath);

            $this->info('Done!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

