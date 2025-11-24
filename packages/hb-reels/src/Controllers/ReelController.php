<?php

namespace HbReels\EventReelGenerator\Controllers;

use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReelController
{
    public function __construct()
    {
    }

    /**
     * Show the event reel generator form.
     */
    public function index()
    {
        return view('eventreel::index');
    }

    /**
     * Generate event reel from uploaded image or text.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'flyer_image' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'event_text' => 'nullable|string|max:2000',
            'show_flyer' => 'nullable|boolean',
            'access_code' => 'nullable|string',
        ]);

        // Check access code if configured
        $requiredAccessCode = config('eventreel.access_code');
        if ($requiredAccessCode && $request->input('access_code') !== $requiredAccessCode) {
            return back()->withErrors(['access_code' => 'Invalid access code.'])->withInput();
        }

        try {
            $ocrService = app(OCRService::class);
            $aiService = app(AIService::class);
            $pexelsService = app(PexelsService::class);
            $videoRenderer = app(VideoRenderer::class);

            $eventText = $this->extractEventText($request, $ocrService);
            $showFlyer = $request->boolean('show_flyer', false);
            $flyerPath = null;

            if ($request->hasFile('flyer_image')) {
                $flyerPath = $this->storeFlyer($request->file('flyer_image'));
            }

            // Generate AI caption for video search
            $caption = $aiService->generateCaption($eventText);

            // Extract structured details from text using AI (handles any content type)
            $contentDetails = $aiService->extractEventDetails($eventText);
            
            // Format overlay text from extracted details
            $overlayText = $this->formatContentOverlay($contentDetails);

            // Get stock video from Pexels
            $stockVideoPath = $pexelsService->downloadVideo($caption);

            // Determine what to show in the video:
            // - If showFlyer is TRUE: Show flyer only, no captions
            // - If showFlyer is FALSE and flyer exists: Show flyer + captions overlay
            // - If showFlyer is FALSE and no flyer: Show stock video + captions
            
            $displayFlyerPath = $flyerPath; // Always use flyer if it exists (background)
            $displayCaption = $showFlyer ? null : $overlayText; // Only hide caption if checkbox is checked
            
            \Log::info('Rendering video', [
                'showFlyer_checkbox' => $showFlyer,
                'flyerPath_exists' => $flyerPath ? 'yes' : 'no',
                'displayFlyerPath' => $displayFlyerPath ? 'yes' : 'no',
                'displayCaption' => $displayCaption,
            ]);
            
            // Render final video
            $outputPath = $videoRenderer->render(
                stockVideoPath: $stockVideoPath,
                flyerPath: $displayFlyerPath,
                caption: $displayCaption
            );

            // Clean up temporary files
            if ($flyerPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($flyerPath);
            }
            if ($stockVideoPath) {
                Storage::disk(config('eventreel.storage.disk'))->delete($stockVideoPath);
            }

            // Return download response
            return Storage::disk(config('eventreel.storage.disk'))->download(
                $outputPath,
                'event-reel-' . now()->format('Y-m-d-His') . '.mp4'
            );

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to generate reel: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Format content details into overlay text with line breaks.
     * Works with any content type (events, announcements, acknowledgements, etc.)
     */
    private function formatContentOverlay(array $details): string
    {
        // Extract lines in order (line1, line2, line3, line4, line5)
        $lines = [];
        for ($i = 1; $i <= 5; $i++) {
            $lineKey = "line{$i}";
            if (isset($details[$lineKey]) && !empty(trim($details[$lineKey]))) {
                $lines[] = trim($details[$lineKey]);
            }
        }
        
        // Filter out empty lines
        $lines = array_filter($lines, fn($line) => !empty(trim($line)));
        
        // Use actual newline character to separate lines
        return implode("\n", $lines);
    }

    private function extractEventText(Request $request, OCRService $ocrService): string
    {
        if ($request->filled('event_text')) {
            return $request->input('event_text');
        }

        if ($request->hasFile('flyer_image')) {
            $imagePath = $request->file('flyer_image')->getRealPath();
            return $ocrService->extractText($imagePath);
        }

        throw new \Exception('Either flyer image or event description must be provided.');
    }

    /**
     * Store uploaded flyer image.
     */
    private function storeFlyer($file): string
    {
        $disk = config('eventreel.storage.disk');
        $path = config('eventreel.storage.temp_path') . '/' . Str::random(40) . '.' . $file->getClientOriginalExtension();
        
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
        
        return $path;
    }
}

