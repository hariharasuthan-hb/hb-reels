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
        string $language = 'auto'
    ): string {
        $disk = config('eventreel.storage.disk');
        $ffmpegPath = config('eventreel.ffmpeg.path', 'ffmpeg');
        
        $width = config('eventreel.video.width', 1080);
        $height = config('eventreel.video.height', 1920);
        $duration = config('eventreel.video.duration', 5);
        $fps = config('eventreel.video.fps', 30);

        // Auto-detect language if not specified
        if ($language === 'auto' && $caption) {
            $language = $this->detectLanguage($caption);
            \Log::info('Auto-detected language', ['language' => $language, 'caption' => $caption]);
        }

        // Get full paths - ensure we handle both relative and absolute paths correctly
        $diskRoot = Storage::disk($disk)->path('');
        if (strpos($stockVideoPath, $diskRoot) === 0) {
            // Path is already absolute, use it directly
            $stockVideoFullPath = $stockVideoPath;
        } else {
            // Path is relative, convert to absolute
            $stockVideoFullPath = Storage::disk($disk)->path($stockVideoPath);
        }
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
            $flyerPath ? (strpos($flyerPath, $diskRoot) === 0 ? $flyerPath : Storage::disk($disk)->path($flyerPath)) : null,
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
    
        // Add caption text overlay if provided using ASS subtitles for proper complex script support
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

            // Use ASS subtitles for proper complex script rendering (Tamil, Arabic, etc.)
            $fontFile = $this->getFontForLanguage($language);

            \Log::info('Text positioning for ASS subtitles', [
                'line_count' => $lineCount,
                'lines' => $lines,
                'y_start' => $yStart,
                'y_step' => $yStep,
                'font_file' => $fontFile,
                'language' => $language
            ]);

            // Generate ASS subtitle file
            $assContent = $this->generateASSSubtitle($lines, $fontFile, $fontSize, $yStart, $yStep, $width, $height);

            // Create temporary ASS file
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $assFilePath = $tempDir . '/' . Str::random(16) . '.ass';
            file_put_contents($assFilePath, $assContent);

            // Add ASS subtitle filter to FFmpeg command
            $filters[] = sprintf("ass='%s'", escapeshellarg($assFilePath));

            // Track temp file for cleanup
            $tempFiles[] = $assFilePath;
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
            'NotoSansBengali-Regular.ttf' => 'Noto Sans Bengali',
            'NotoSansGujarati-Regular.ttf' => 'Noto Sans Gujarati',
            'NotoSansArabic-Regular.ttf' => 'Noto Sans Arabic',
            'NotoSansThai-Regular.ttf' => 'Noto Sans Thai',
            'NotoSansCJK-Regular.ttc' => 'Noto Sans CJK JP',
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
     * Prioritizes package fonts over system fonts for consistent rendering.
     */
    private function getFontForLanguage(string $language): ?string
    {
        // Check if custom font path is configured
        $customFont = config('eventreel.video.font_path');
        if ($customFont && file_exists($customFont)) {
            return $customFont;
        }

        // PRIORITY 1: Package fonts (guaranteed to be correct Noto fonts)
        $packageFonts = $this->getPackageFontForLanguage($language);
        if ($packageFonts) {
            return $packageFonts;
        }

        // PRIORITY 2: System fonts (fallback)
        $systemFont = $this->getSystemFontForLanguage($language);
        if ($systemFont) {
            return $systemFont;
        }

        // PRIORITY 3: System default fonts
        $fallbackFonts = [
            '/System/Library/Fonts/Supplemental/Arial.ttf',  // macOS
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',  // Linux
            'C:\\Windows\\Fonts\\arial.ttf',  // Windows
        ];

        foreach ($fallbackFonts as $font) {
            if (file_exists($font)) {
                \Log::warning('Using system fallback font (may not support all characters)', [
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
     * Get font from package resources (highest priority).
     */
    private function getPackageFontForLanguage(string $language): ?string
    {
        // Map languages to package font files
        $packageFontMap = [
            'ta' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansTamil-Regular.ttf',
            'hi' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansDevanagari-Regular.ttf',
            'te' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansTelugu-Regular.ttf',
            'ml' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansMalayalam-Regular.ttf',
            'kn' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansKannada-Regular.ttf',
            'bn' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansBengali-Regular.ttf',
            'gu' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansGujarati-Regular.ttf',
            'mr' => __DIR__ . '/../../resources/fonts/noto-sans-indic/NotoSansDevanagari-Regular.ttf',
            'ar' => __DIR__ . '/../../resources/fonts/noto-sans-arabic/NotoSansArabic-Regular.ttf',
            'fa' => __DIR__ . '/../../resources/fonts/noto-sans-arabic/NotoSansArabic-Regular.ttf',
            'ur' => __DIR__ . '/../../resources/fonts/noto-sans-arabic/NotoSansArabic-Regular.ttf',
            'th' => __DIR__ . '/../../resources/fonts/noto-sans-thai/NotoSansThai-Regular.ttf',
            'zh' => __DIR__ . '/../../resources/fonts/noto-sans-cjk/NotoSansCJK-Regular.ttc',
            'ja' => __DIR__ . '/../../resources/fonts/noto-sans-cjk/NotoSansCJK-Regular.ttc',
            'ko' => __DIR__ . '/../../resources/fonts/noto-sans-cjk/NotoSansCJK-Regular.ttc',
        ];

        $fontPath = $packageFontMap[$language] ?? null;

        if ($fontPath && file_exists($fontPath)) {
            \Log::info('Using package font', [
                'language' => $language,
                'font_path' => $fontPath
            ]);
            return $fontPath;
        }

        return null;
    }

    /**
     * Get font from system directories (fallback).
     */
    private function getSystemFontForLanguage(string $language): ?string
    {
        // Map languages to their required fonts
        $fontMap = [
            // Indic languages
            'hi' => 'NotoSansDevanagari-Regular',
            'ta' => 'NotoSansTamil-Regular',
            'te' => 'NotoSansTelugu-Regular',
            'ml' => 'NotoSansMalayalam-Regular',
            'kn' => 'NotoSansKannada-Regular',
            'bn' => 'NotoSansBengali-Regular',
            'gu' => 'NotoSansGujarati-Regular',
            'pa' => 'NotoSansGurmukhi-Regular',
            'or' => 'NotoSansOriya-Regular',
            'mr' => 'NotoSansDevanagari-Regular',

            // Southeast Asian languages
            'th' => 'NotoSansThai-Regular',
            'my' => 'NotoSansMyanmar-Regular',
            'km' => 'NotoSansKhmer-Regular',
            'lo' => 'NotoSansLao-Regular',

            // East Asian languages
            'zh' => 'NotoSansCJK',
            'ja' => 'NotoSansCJK',
            'ko' => 'NotoSansCJK',

            // Arabic and related scripts
            'ar' => 'Noto Sans Arabic',
            'fa' => 'Noto Sans Arabic',
            'ur' => 'Noto Sans Arabic',

            // Cyrillic
            'ru' => 'Noto Sans',
            'uk' => 'Noto Sans',

            // Default
            'default' => 'Noto Sans'
        ];

        $fontName = $fontMap[$language] ?? $fontMap['default'];

        // Get home directory safely
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '/Users/' . get_current_user());

        // System font search paths
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
        ];

        foreach ($searchPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            try {
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

                    if (!in_array($extension, ['ttf', 'otf', 'ttc'])) {
                        continue;
                    }

                    $fileBaseName = pathinfo($filename, PATHINFO_FILENAME);

                    // Check for exact or fuzzy match
                    $exactMatch = ($fileBaseName === $fontName);
                    $searchName = str_replace([' ', '-', '_'], '', strtolower($fontName));
                    $fileBaseNameNormalized = str_replace([' ', '-', '_'], '', strtolower($fileBaseName));
                    $fuzzyMatch = (strpos($fileBaseNameNormalized, $searchName) !== false);

                    if ($exactMatch || $fuzzyMatch) {
                        // Skip condensed fonts
                        if (stripos($filename, 'Condensed') !== false ||
                            stripos($filename, 'Cond') !== false ||
                            stripos($filename, 'ExtCond') !== false ||
                            stripos($filename, 'SemCond') !== false) {
                            continue;
                        }

                        // Prefer Regular fonts
                        if (stripos($filename, 'Regular') !== false) {
                            \Log::info('Using system Regular font', [
                                'language' => $language,
                                'font_name' => $fontName,
                                'font_path' => $file->getPathname()
                            ]);
                            return $file->getPathname();
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::debug('Skipping font search path', [
                    'path' => $path,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * Auto-detect language from text content using Unicode script ranges.
     */
    private function detectLanguage(string $text): string
    {
        // Remove newlines and extra spaces for cleaner detection
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if (empty($text)) {
            return 'en';
        }

        // Count characters in different Unicode script ranges
        $scriptCounts = [
            'tamil' => 0,      // Tamil script (0B80-0BFF)
            'devanagari' => 0, // Hindi, Marathi, Sanskrit (0900-097F)
            'telugu' => 0,     // Telugu (0C00-0C7F)
            'malayalam' => 0,  // Malayalam (0D00-0D7F)
            'kannada' => 0,    // Kannada (0C80-0CFF)
            'bengali' => 0,    // Bengali (0980-09FF)
            'gujarati' => 0,   // Gujarati (0A80-0AFF)
            'arabic' => 0,     // Arabic, Persian, Urdu (0600-06FF)
            'thai' => 0,       // Thai (0E00-0E7F)
            'cjk' => 0,        // Chinese, Japanese, Korean (4E00-9FFF, 3040-309F, 30A0-30FF, etc.)
            'latin' => 0,      // Latin script (0041-005A, 0061-007A)
        ];

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');

            if ($code >= 0x0B80 && $code <= 0x0BFF) {
                $scriptCounts['tamil']++;
            } elseif ($code >= 0x0900 && $code <= 0x097F) {
                $scriptCounts['devanagari']++;
            } elseif ($code >= 0x0C00 && $code <= 0x0C7F) {
                $scriptCounts['telugu']++;
            } elseif ($code >= 0x0D00 && $code <= 0x0D7F) {
                $scriptCounts['malayalam']++;
            } elseif ($code >= 0x0C80 && $code <= 0x0CFF) {
                $scriptCounts['kannada']++;
            } elseif ($code >= 0x0980 && $code <= 0x09FF) {
                $scriptCounts['bengali']++;
            } elseif ($code >= 0x0A80 && $code <= 0x0AFF) {
                $scriptCounts['gujarati']++;
            } elseif ($code >= 0x0600 && $code <= 0x06FF) {
                $scriptCounts['arabic']++;
            } elseif ($code >= 0x0E00 && $code <= 0x0E7F) {
                $scriptCounts['thai']++;
            } elseif (($code >= 0x4E00 && $code <= 0x9FFF) ||
                     ($code >= 0x3040 && $code <= 0x309F) ||
                     ($code >= 0x30A0 && $code <= 0x30FF) ||
                     ($code >= 0xAC00 && $code <= 0xD7AF)) {
                $scriptCounts['cjk']++;
            } elseif (($code >= 0x0041 && $code <= 0x005A) ||
                     ($code >= 0x0061 && $code <= 0x007A)) {
                $scriptCounts['latin']++;
            }
        }

        // Find the script with the most characters
        $maxScript = 'latin'; // Default fallback
        $maxCount = 0;

        foreach ($scriptCounts as $script => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $maxScript = $script;
            }
        }

        // Map detected scripts to language codes
        $scriptToLanguage = [
            'tamil' => 'ta',
            'devanagari' => 'hi', // Could be hi, mr, sa, etc. - default to hi
            'telugu' => 'te',
            'malayalam' => 'ml',
            'kannada' => 'kn',
            'bengali' => 'bn',
            'gujarati' => 'gu',
            'arabic' => 'ar', // Could be ar, fa, ur - default to ar
            'thai' => 'th',
            'cjk' => 'zh', // Could be zh, ja, ko - default to zh
            'latin' => 'en', // Default to English for Latin script
        ];

        $detectedLanguage = $scriptToLanguage[$maxScript] ?? 'en';

        \Log::info('Language detection result', [
            'text' => $text,
            'script_counts' => $scriptCounts,
            'detected_script' => $maxScript,
            'language' => $detectedLanguage
        ]);

        return $detectedLanguage;
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

