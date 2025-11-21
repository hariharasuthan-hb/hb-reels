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

            // Generate AI caption
            $caption = $aiService->generateCaption($eventText);

            // Get stock video from Pexels
            $stockVideoPath = $pexelsService->downloadVideo($caption);

            // Render final video
            $outputPath = $videoRenderer->render(
                stockVideoPath: $stockVideoPath,
                flyerPath: $showFlyer ? $flyerPath : null,
                caption: $showFlyer ? null : $caption
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
     * Extract event text from uploaded image or use provided text.
     */
    private function extractEventText(Request $request, OCRService $ocrService): string
    {
        if ($request->filled('event_text')) {
            return $request->input('event_text');
        }

        if ($request->hasFile('flyer_image')) {
            $imagePath = $request->file('flyer_image')->getRealPath();
            return $ocrService->extractText($imagePath);
        }

        throw new \Exception('Either flyer image or event text must be provided.');
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

