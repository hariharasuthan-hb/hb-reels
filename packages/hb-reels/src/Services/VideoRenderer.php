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
        ?string $caption = null,
        string $language = 'en'
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
            $caption,
            $language
        );

        // Execute FFmpeg
        exec($command . ' 2>&1', $output, $returnCode);

        // Clean up temporary files
        $tempDir = storage_path('app/temp');
        if (is_dir($tempDir)) {
            // Clean up old temporary files
            $oldTempFiles = glob($tempDir . '/*');
            foreach ($oldTempFiles as $tempFile) {
                // Only delete files older than 5 minutes to avoid conflicts
                if (file_exists($tempFile) && (time() - filemtime($tempFile)) > 300) {
                    @unlink($tempFile);
                }
            }
        }

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
        ?string $caption = null,
        string $language = 'en'
    ): string {
        $filters = [];
        $inputs = [];
        $tempFiles = [];  // Track temporary text files for cleanup
    
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
    
        // Add caption text overlay if provided
        \Log::info('VideoRenderer caption check', [
            'caption_provided' => $caption ? 'yes' : 'no',
            'caption_content' => $caption,
            'flyerPath_provided' => $flyerPath ? 'yes' : 'no'
        ]);

        if ($caption) {
            $caption = str_replace('\\n', "\n", $caption);
            $lines = preg_split('/\r\n|\r|\n/', $caption, -1, PREG_SPLIT_NO_EMPTY);

            $isNonLatin = in_array($language, [
                'ta','hi','te','ml','kn','bn','gu','pa','or','mr',
                'th','my','km','lo','zh','ja','ko','ar','fa','ur'
            ]);

            $needsMaxLigatureSupport = in_array($language, [
                'ta','hi','te','ml','kn','bn','gu','pa','or','mr',
                'ar','fa','ur','th','my','km','lo'
            ]);

            $maxCharsPerLine = $needsMaxLigatureSupport ? 20 : ($isNonLatin ? 30 : 40);

            $wrappedLines = [];
            foreach ($lines as $line) {
                $lineLength = mb_strlen($line, 'UTF-8');

                if ($lineLength > $maxCharsPerLine) {
                    $words = explode(' ', $line);
                    $currentLine = '';

                    foreach ($words as $word) {
                        if (empty($currentLine)) {
                            $currentLine = $word;
                        } else {
                            $testLine = $currentLine . ' ' . $word;
                            if (mb_strlen($testLine, 'UTF-8') <= $maxCharsPerLine) {
                                $currentLine = $testLine;
                            } else {
                                $wrappedLines[] = $currentLine;
                                $currentLine = $word;
                            }
                        }
                    }
                    if ($currentLine) {
                        $wrappedLines[] = $currentLine;
                    }
                } else {
                    $wrappedLines[] = $line;
                }
            }

            $lines = $wrappedLines;
            $lineCount = count($lines);

            if ($isNonLatin) {
                if ($needsMaxLigatureSupport) {
                    if ($lineCount <= 5) {
                        $fontSize = 56;
                        $yStep = 120;
                    } else if ($lineCount <= 7) {
                        $fontSize = 52;
                        $yStep = 115;
                    } else if ($lineCount <= 10) {
                        $fontSize = 50;
                        $yStep = 110;
                    } else {
                        $fontSize = 48;
                        $yStep = 105;
                    }
                } else {
                    if ($lineCount <= 5) {
                        $fontSize = 44;
                        $yStep = 100;
                    } else if ($lineCount <= 7) {
                        $fontSize = 42;
                        $yStep = 95;
                    } else if ($lineCount <= 10) {
                        $fontSize = 40;
                        $yStep = 90;
                    } else {
                        $fontSize = 38;
                        $yStep = 85;
                    }
                }
            } else {
                $fontSize = 56;
                $yStep = 100;
            }

            $totalTextHeight = ($lineCount * $yStep);
            $maxHeight = $height - 400;

            if ($totalTextHeight > $maxHeight) {
                $yStep = intval($maxHeight / $lineCount);
                $totalTextHeight = ($lineCount * $yStep);
            }

            if ($flyerPath) {
                $yStart = intval(($height - $totalTextHeight) / 2) + 50;
            } else {
                $yStart = intval(($height - $totalTextHeight) / 2);
                if ($yStart < 200) {
                    $yStart = 200;
                }
            }

            // QUICKTIME COMPATIBILITY FIX: Use drawtext instead of ASS subtitles
            // ASS subtitles work great in VLC/MPC but QuickTime has limited subtitle support
            $fontFile = $this->getFontForLanguage($language);
            $currentY = $yStart;

            \Log::info('Text positioning for drawtext', [
                'line_count' => $lineCount,
                'lines' => $lines,
                'y_start' => $yStart,
                'y_step' => $yStep,
                'font_file' => $fontFile,
                'language' => $language
            ]);

            // Count non-empty lines for proper labeling
            $nonEmptyLines = array_filter($lines, function($line) {
                return trim($line) !== '';
            });
            $totalNonEmptyLines = count($nonEmptyLines);

            $lineIndex = 0;
            $processedLineIndex = 0;
            foreach ($lines as $line) {
                // Skip empty lines
                if (trim($line) === '') {
                    continue;
                }

                \Log::info("Processing line {$processedLineIndex} with drawtext", ['text' => $line, 'y_position' => $currentY]);

                // Escape special characters for FFmpeg
                $safe = str_replace("'", "", $line);
                $safe = str_replace(':', '\\:', $safe);

                // Wrap long text manually (FFmpeg doesn't have auto text wrapping)
                // Use higher limit for Unicode text (Tamil, etc.) since characters take more bytes
                $wrapLimit = in_array($language, ['ta', 'hi', 'te', 'ml', 'kn', 'bn', 'gu', 'pa', 'or', 'mr', 'th', 'my', 'km', 'lo', 'zh', 'ja', 'ko', 'ar', 'fa', 'ur'])
                    ? 25 // Lower limit for complex scripts
                    : 35; // Standard limit for Latin scripts
                $safe = $this->wrapText($safe, $wrapLimit);

                // Create unique stream labels for each line to chain them properly
                $inputLabel = $processedLineIndex === 0 ? '[v]' : "[v{$processedLineIndex}]";
                // For the last non-empty line, output directly to [v] to connect to final processing
                $isLastLine = $processedLineIndex === $totalNonEmptyLines - 1;
                $outputLabel = $isLastLine ? '[v]' : "[v" . ($processedLineIndex + 1) . "]";

                // Draw text with shadow and border for maximum visibility on any background
                if ($fontFile && file_exists($fontFile)) {
                    $filters[] = sprintf(
                        "%sdrawtext=fontfile=%s:text='%s':fontsize=%d:fontcolor=white:" .
                        "x=(w-text_w)/2:y=%d:" .
                        "borderw=3:bordercolor=black:" .
                        "shadowcolor=black@0.8:shadowx=2:shadowy=2%s",
                        $inputLabel,
                        escapeshellarg($fontFile),
                        $safe,
                        $fontSize,
                        $currentY,
                        $outputLabel
                    );
                } else {
                    $filters[] = sprintf(
                        "%sdrawtext=text='%s':fontsize=%d:fontcolor=white:" .
                        "x=(w-text_w)/2:y=%d:" .
                        "borderw=3:bordercolor=black:" .
                        "shadowcolor=black@0.8:shadowx=2:shadowy=2%s",
                        $inputLabel,
                        $safe,
                        $fontSize,
                        $currentY,
                        $outputLabel
                    );
                }

                $currentY += $yStep;
                $processedLineIndex++;
                $lineIndex++;
            }

            // Final output is already connected to [v] from the last drawtext filter
        }
    
        // Apply final video processing
        $filters[] = '[v]trim=duration=' . $duration . ',setpts=PTS-STARTPTS,fps=' . $fps . '[vout]';
    
        $filterComplex = implode(';', $filters);
    
        $command = sprintf(
            '%s %s -filter_complex "%s" -map "[vout]" -t %d -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -movflags +faststart %s',
            escapeshellarg($ffmpegPath),
            implode(' ', $inputs),
            $filterComplex,
            $duration,
            escapeshellarg($outputPath)
        );
    
        \Log::info('========== FINAL FFMPEG COMMAND ==========');
        \Log::info('FFmpeg command', [
            'command_length' => strlen($command),
            'full_command' => $command
        ]);
    
        return $command;
    }
    
    
    
    /**
     * Generate ASS subtitle content with proper Tamil ligature support.
     * ASS format uses libass which has full HarfBuzz text shaping.
     */
    private function generateASSSubtitle(
        array $lines,
        ?string $fontFile,
        int $fontSize,
        int $yStart,
        int $yStep,
        int $width,
        int $height
    ): string {
        // Get proper font family name for ASS format
        // ASS needs the actual font family name, not the filename
        $fontName = $this->getFontFamilyName($fontFile);
        
        // ASS file header
        $ass = "[Script Info]\n";
        $ass .= "Title: Video Captions\n";
        $ass .= "ScriptType: v4.00+\n";
        $ass .= "WrapStyle: 0\n";
        $ass .= "PlayResX: {$width}\n";
        $ass .= "PlayResY: {$height}\n";
        $ass .= "ScaledBorderAndShadow: yes\n\n";
        
        // Styles section with Tamil-optimized settings for maximum clarity
        $ass .= "[V4+ Styles]\n";
        $ass .= "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n";
        
        // MAXIMUM caption clarity for ALL complex script languages (25+ languages supported):
        // - Pure white text (&H00FFFFFF)
        // - EXTRA THICK black outline (5.0) for ultimate contrast on any background
        // - Bold weight (-1) for thick, clear strokes
        // - MAXIMUM character spacing (5) for perfect ligature separation
        // - Deep shadow (4) for strong 3D depth effect
        // - Center alignment (5) for professional look
        // - Semi-transparent background box (&HA0000000) for readability on any background
        // - BorderStyle=3 for background box like YouTube captions
        // 
        // Supported complex script languages with HarfBuzz shaping via libass:
        // ✅ Indic: Tamil, Hindi, Telugu, Malayalam, Kannada, Bengali, Gujarati, Punjabi, Oriya, Marathi
        // ✅ Arabic/RTL: Arabic, Persian (Farsi), Urdu
        // ✅ Southeast Asian: Thai, Burmese (Myanmar), Khmer, Lao
        // ✅ East Asian: Chinese, Japanese, Korean
        // ✅ Latin: English, Spanish, French, German, Italian, Portuguese, Russian, Ukrainian, etc.
        //
        // This ASS style ensures ALL ligatures render perfectly (குடில், नाटक, అక్షర, പദം, ಅಕ್ಷರ, etc.)
        $ass .= sprintf(
            "Style: Default,%s,%d,&H00FFFFFF,&H000000FF,&H00000000,&HA0000000,-1,0,0,0,100,100,5,0,3,5.0,4,5,30,30,30,1\n\n",
            $fontName,
            $fontSize
        );
        
        // Events section
        $ass .= "[Events]\n";
        $ass .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";
        
        // Add each line as a dialogue event
        $currentY = $yStart;
        foreach ($lines as $lineIndex => $line) {
            if (trim($line) === '') {
                continue;
            }
            
            // Calculate position using {\an5\pos(x,y)} override tag
            // \an5 = center alignment (both horizontal and vertical anchor at center)
            $posX = intval($width / 2);  // Center horizontally
            $posY = $currentY;
            
            // ASS uses Start and End times (00:00:00.00 format)
            // Show for entire video duration
            $line = str_replace("\n", "\\N", $line);  // Escape newlines in ASS format
            
            $ass .= sprintf(
                "Dialogue: 0,0:00:00.00,0:00:10.00,Default,,0,0,0,,{\\an5\\pos(%d,%d)}%s\n",
                $posX,
                $posY,
                $line
            );
            
            $currentY += $yStep;
        }
        
        return $ass;
    }
    
    /**
     * Get the proper font family name from font file path.
     * ASS format needs the actual font family name, not the filename.
     */
    private function getFontFamilyName(?string $fontFile): string
    {
        if (!$fontFile || !file_exists($fontFile)) {
            return 'Arial';
        }
        
        // Map of font filenames to their proper family names
        $fontFamilyMap = [
            'NotoSansTamil-Regular.ttf' => 'Noto Sans Tamil',
            'NotoSansTamil-Bold.ttf' => 'Noto Sans Tamil',
            'NotoSansTamilUI-Regular.ttf' => 'Noto Sans Tamil UI',
            'NotoSansDevanagari-Regular.ttf' => 'Noto Sans Devanagari',
            'NotoSansTelugu-Regular.ttf' => 'Noto Sans Telugu',
            'NotoSansMalayalam-Regular.ttf' => 'Noto Sans Malayalam',
            'NotoSansKannada-Regular.ttf' => 'Noto Sans Kannada',
            'NotoSans-Regular.ttf' => 'Noto Sans',
        ];
        
        $filename = basename($fontFile);
        
        if (isset($fontFamilyMap[$filename])) {
            return $fontFamilyMap[$filename];
        }
        
        // Fallback: remove file extension and hyphens/underscores
        return str_replace(['-', '_'], ' ', basename($fontFile, '.ttf'));
    }
    
    /**
     * Get appropriate font file for the specified language.
     * Returns path to Noto Sans font that supports the language's script.
     */
    private function getFontForLanguage(string $language): ?string
    {
        // Check if custom font path is configured
        $customFont = config('eventreel.video.font_path');
        if ($customFont && file_exists($customFont)) {
            return $customFont;
        }
        
        // Map languages to their required fonts
        // Uniform approach: All languages use Regular weight for consistency
        // ALL complex script languages use ASS subtitles with HarfBuzz shaping via libass
        $fontMap = [
            // Indic languages (complex ligatures - need HarfBuzz shaping)
            'hi' => 'NotoSansDevanagari-Regular',  // Hindi (Devanagari script)
            'ta' => 'NotoSansTamil-Regular',       // Tamil
            'te' => 'NotoSansTelugu-Regular',      // Telugu
            'ml' => 'NotoSansMalayalam-Regular',   // Malayalam
            'kn' => 'NotoSansKannada-Regular',     // Kannada
            'bn' => 'NotoSansBengali-Regular',     // Bengali (Bangla)
            'gu' => 'NotoSansGujarati-Regular',    // Gujarati
            'pa' => 'NotoSansGurmukhi-Regular',    // Punjabi (Gurmukhi script)
            'or' => 'NotoSansOriya-Regular',       // Oriya (Odia)
            'mr' => 'NotoSansDevanagari-Regular',  // Marathi (uses Devanagari)
            
            // Southeast Asian languages (complex scripts)
            'th' => 'NotoSansThai-Regular',        // Thai
            'my' => 'NotoSansMyanmar-Regular',     // Burmese (Myanmar)
            'km' => 'NotoSansKhmer-Regular',       // Khmer (Cambodian)
            'lo' => 'NotoSansLao-Regular',         // Lao
            
            // East Asian languages (all use the same CJK font file)
            'zh' => 'NotoSansCJK',                 // Chinese (Simplified)
            'ja' => 'NotoSansCJK',                 // Japanese
            'ko' => 'NotoSansCJK',                 // Korean
            
            // Arabic and related scripts (RTL languages)
            'ar' => 'Noto Sans Arabic',            // Arabic
            'fa' => 'Noto Sans Arabic',            // Persian (Farsi)
            'ur' => 'Noto Sans Arabic',            // Urdu
            
            // Cyrillic
            'ru' => 'Noto Sans',                   // Russian
            'uk' => 'Noto Sans',                   // Ukrainian
            
            // Default for Western languages (English, Spanish, French, German, Italian, Portuguese, etc.)
            'default' => 'Noto Sans'
        ];
        
        // Get font name for language
        $fontName = $fontMap[$language] ?? $fontMap['default'];
        
        // Get home directory safely
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '/Users/' . get_current_user());
        
        // Platform-specific font search paths
        $searchPaths = [
            // macOS
            $homeDir . '/Library/Fonts/',
            '/Library/Fonts/',
            '/System/Library/Fonts/',
            '/System/Library/Fonts/Supplemental/',
            
            // Linux
            '/usr/share/fonts/',
            '/usr/share/fonts/truetype/',
            '/usr/share/fonts/truetype/noto/',
            '/usr/local/share/fonts/',
            $homeDir . '/.fonts/',
            
            // Windows
            'C:/Windows/Fonts/',
            
            // Custom storage
            storage_path('fonts/'),
        ];
        
        // Try to find the font file
        // Collect all matching fonts with priority: exact match > bold > regular > medium
        $foundFonts = [
            'exact' => null,     // Exact filename match (highest priority)
            'bold' => null,      // Bold weight (for ta language)
            'regular' => null,   // Regular weight
            'medium' => null,    // Medium weight (fallback)
        ];
        
        foreach ($searchPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            try {
                // Search for font files recursively
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    
                    $filename = $file->getFilename();
                    $extension = strtolower($file->getExtension());
                    
                    // Check if this is a font file
                    if (!in_array($extension, ['ttf', 'otf', 'ttc'])) {
                        continue;
                    }
                    
                    // First try exact match (for specific font names like "NotoSansTamil-Regular")
                    $fileBaseName = pathinfo($filename, PATHINFO_FILENAME);
                    $exactMatch = ($fileBaseName === $fontName);
                    
                    // Then try fuzzy match (case-insensitive, ignoring spaces/dashes)
                    $searchName = str_replace([' ', '-', '_'], '', strtolower($fontName));
                    $fileBaseNameNormalized = str_replace([' ', '-', '_'], '', strtolower($fileBaseName));
                    $fuzzyMatch = (strpos($fileBaseNameNormalized, $searchName) !== false);
                    
                    if ($exactMatch || $fuzzyMatch) {
                        
                        // STRICTLY reject Condensed/ExtraCondensed/SemiCondensed fonts
                        if (stripos($filename, 'Condensed') !== false ||
                            stripos($filename, 'Cond') !== false ||
                            stripos($filename, 'ExtCond') !== false ||
                            stripos($filename, 'SemCond') !== false) {
                            continue; // Skip condensed fonts
                        }
                        
                        // Collect fonts by priority
                        
                        // 1. Exact match (highest priority)
                        if ($exactMatch && !$foundFonts['exact']) {
                            $foundFonts['exact'] = $file->getPathname();
                        }
                        
                        // 2. Bold fonts (for Tamil video clarity)
                        if (stripos($filename, 'Bold') !== false &&
                            !stripos($filename, 'ExtraBold') &&  // Skip ExtraBold
                            !stripos($filename, 'SemiBold') &&   // Skip SemiBold
                            !$foundFonts['bold']) {
                            $foundFonts['bold'] = $file->getPathname();
                        }
                        
                        // 3. Regular fonts
                        if (stripos($filename, 'Regular') !== false && !$foundFonts['regular']) {
                            $foundFonts['regular'] = $file->getPathname();
                        }
                        
                        // 4. Medium fonts as fallback
                        if (stripos($filename, 'Medium') !== false &&
                            !stripos($filename, 'Bold') &&
                            !stripos($filename, 'Light') &&
                            !stripos($filename, 'Thin') &&
                            !$foundFonts['medium']) {
                            $foundFonts['medium'] = $file->getPathname();
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip paths that cause errors (permission denied, etc.)
                \Log::debug('Skipping font search path', [
                    'path' => $path,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        // Uniform font priority for all languages: Exact > Regular > Medium > Bold
        // This ensures consistent behavior across all languages
        
        // 1. Exact match (highest priority)
        if ($foundFonts['exact']) {
            \Log::info('Found EXACT MATCH font', [
                'language' => $language,
                'font_name' => $fontName,
                'font_path' => $foundFonts['exact']
            ]);
            return $foundFonts['exact'];
        }
        
        // 2. Regular font (preferred for all languages)
        if ($foundFonts['regular']) {
            \Log::info('Found REGULAR font', [
                'language' => $language,
                'font_name' => $fontName,
                'font_path' => $foundFonts['regular']
            ]);
            return $foundFonts['regular'];
        }
        
        // 3. Medium font (fallback)
        if ($foundFonts['medium']) {
            \Log::info('Found MEDIUM font', [
                'language' => $language,
                'font_name' => $fontName,
                'font_path' => $foundFonts['medium']
            ]);
            return $foundFonts['medium'];
        }
        
        // 4. Bold font (last resort fallback)
        if ($foundFonts['bold']) {
            \Log::info('Found BOLD font (fallback)', [
                'language' => $language,
                'font_name' => $fontName,
                'font_path' => $foundFonts['bold']
            ]);
            return $foundFonts['bold'];
        }
        
        // Fallback to system default fonts
        $fallbackFonts = [
            '/System/Library/Fonts/Supplemental/Arial.ttf',  // macOS
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',  // Linux
            'C:\\Windows\\Fonts\\arial.ttf',  // Windows
        ];
        
        foreach ($fallbackFonts as $font) {
            if (file_exists($font)) {
                \Log::warning('Using fallback font (may not support all characters)', [
                    'language' => $language,
                    'fallback_font' => $font
                ]);
                return $font;
            }
        }
        
        \Log::error('No suitable font found for language', ['language' => $language]);
        return null;
    }

    /**
     * Wrap text at a specified character width for FFmpeg drawtext.
     * Uses proper Unicode character counting for multilingual text.
     */
    private function wrapText(string $text, int $maxChars): string
    {
        // Use mb_strlen for proper Unicode character counting
        if (mb_strlen($text, 'UTF-8') <= $maxChars) {
            return $text;
        }

        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            if (mb_strlen($testLine, 'UTF-8') <= $maxChars) {
                $currentLine = $testLine;
            } else {
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }

        if ($currentLine) {
            $lines[] = $currentLine;
        }

        // Use literal \n for FFmpeg drawtext (will be rendered as newline)
        return implode('\\n', $lines);
    }
}

