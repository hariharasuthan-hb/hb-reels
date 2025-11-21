<?php

namespace HbReels\EventReelGenerator\Services;

class OCRService
{
    /**
     * Extract text from image using Tesseract OCR.
     */
    public function extractText(string $imagePath): string
    {
        $tesseractPath = config('eventreel.tesseract.path', 'tesseract');
        $language = config('eventreel.tesseract.language', 'eng');
        
        // Create temporary output file
        $outputFile = sys_get_temp_dir() . '/' . uniqid('ocr_', true) . '.txt';
        
        // Run Tesseract
        $command = sprintf(
            '%s "%s" "%s" -l %s 2>&1',
            escapeshellarg($tesseractPath),
            escapeshellarg($imagePath),
            escapeshellarg($outputFile),
            escapeshellarg($language)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('OCR failed: ' . implode("\n", $output));
        }
        
        // Read extracted text
        $text = file_get_contents($outputFile . '.txt');
        
        // Clean up
        if (file_exists($outputFile . '.txt')) {
            unlink($outputFile . '.txt');
        }
        
        return trim($text) ?: 'Event details';
    }
}

