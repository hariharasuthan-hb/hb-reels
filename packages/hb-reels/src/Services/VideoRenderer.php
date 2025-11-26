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

        // Initialize tempFiles to prevent undefined variable errors
        $tempFiles = [];

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
        $outputPath = config('eventreel.storage.output_path') . '/' . time() . '_' . Str::random(6) . '.mp4';
        $outputFullPath = Storage::disk($disk)->path($outputPath);

        // Ensure output path always has .mp4 extension for FFmpeg compatibility
        if (!preg_match('/\.mp4$/i', $outputFullPath)) {
            $outputFullPath .= '.mp4';
        }

        // Ensure output directory exists
        $outputDir = dirname($outputFullPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Build FFmpeg command
        $result = $this->buildFFmpegCommand(
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

        $command = $result['command'];
        $tempFiles = $result['tempFiles'];
        // Set fontconfig environment for proper Tamil shaping (force fontconfig over macOS CoreText)
        $fontDir = dirname($this->getFontForLanguage($language ?? 'en'));
        putenv("FC_CONFIG_DIR={$fontDir}");
        putenv("FONTCONFIG_PATH={$fontDir}");

        // CRITICAL: Force libass to use fontconfig instead of macOS CoreText
        // Multiple environment variables to ensure fontconfig is used
        putenv("LIBASS_FONT_PROVIDER=fontconfig");
        putenv("ASS_FONT_PROVIDER=fontconfig");
        putenv("FONTCONFIG_USE_CORETEXT=0");

        // Additional fontconfig setup
        putenv("FREETYPE_PROPERTIES=truetype:interpreter-version=40");

        // Validate output path before execution
        if (!preg_match('/\.mp4$/i', $outputPath)) {
            throw new \Exception('Output path must have .mp4 extension: ' . $outputPath);
        }

        // Check if output path looks like caption text (contains only Unicode without path separators)
        if (preg_match('/^[^\/]*$/', $outputPath) && !preg_match('/\.mp4$/i', $outputPath)) {
            throw new \Exception('Output path appears to be just a filename without extension: ' . $outputPath);
        }

        // Additional check: ensure output path contains proper directory structure
        if (strpos($outputPath, 'eventreel/output') === false) {
            throw new \Exception('Output path does not contain expected directory structure: ' . $outputPath);
        }

        // Validate ASS files exist before running FFmpeg
        if (!empty($tempFiles)) {
            foreach ($tempFiles as $tempFile) {
                if (!file_exists($tempFile)) {
                    throw new \Exception('ASS subtitle file not found before FFmpeg execution: ' . $tempFile);
                }
                \Log::info('ASS file validated before FFmpeg', [
                    'file' => $tempFile,
                    'exists' => file_exists($tempFile),
                    'size' => file_exists($tempFile) ? filesize($tempFile) : 0,
                    'permissions' => file_exists($tempFile) ? substr(sprintf('%o', fileperms($tempFile)), -4) : 'N/A'
                ]);
            }
        }

        // Execute FFmpeg
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            // Keep temp files for debugging on failure
            \Log::error('FFmpeg failed, keeping temp files for debugging', [
                'temp_files' => $tempFiles,
                'error_output' => implode("\n", $output),
                'ffmpeg_command' => $command
            ]);

            // Log ASS file contents for debugging
            if (!empty($tempFiles)) {
                foreach ($tempFiles as $tempFile) {
                    if (file_exists($tempFile)) {
                        $content = file_get_contents($tempFile);
                        \Log::error('ASS file content for debugging', [
                            'file' => $tempFile,
                            'content_length' => strlen($content),
                            'content' => strlen($content) < 1000 ? $content : substr($content, 0, 1000) . '...[truncated]'
                        ]);
                    }
                }
            }

            throw new \Exception('Video rendering failed: ' . implode("\n", $output));
        }

        // Clean up temporary ASS files only after successful rendering
        if (!empty($tempFiles)) {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                    \Log::info('Cleaned up temporary ASS file after successful rendering', ['file' => $tempFile]);
                }
            }
        }

        return $outputPath;
    }

    /**
     * Build FFmpeg command based on rendering mode.
     * Returns array with 'command' and 'tempFiles' keys.
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
    ): array {

        $filters = [];
        $inputs = [];
        $tempFiles = [];  // Track temporary text files for cleanup
        $hasCaption = !empty($caption);
        $hasFlyer = !empty($flyerPath);

        // Input 1: Stock video (always present)
        $inputs[] = sprintf('-i %s', escapeshellarg($stockVideoPath));

        // Input 2: Flyer (if provided)
        if ($hasFlyer) {
            $inputs[] = sprintf('-i %s', escapeshellarg($flyerPath));
        }

        // Handle 4 scenarios:
        // 1. Video only (no flyer, no caption) - just scale
        // 2. Video + Caption only
        // 3. Video + Flyer (no caption)
        // 4. Video + Flyer + Caption

        if (!$hasFlyer && !$hasCaption) {
            // Scenario 1: Video only - just scale and trim
            $filters[] = sprintf(
                '[0:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1[v];[v]trim=duration=%d,setpts=PTS-STARTPTS,fps=%d[vout]',
                $width,
                $height,
                $width,
                $height,
                $duration,
                $fps
            );
        } elseif (!$hasFlyer && $hasCaption) {
            // Scenario 2: Video + Caption only - USING ASS SUBTITLES
            \Log::info('Using ASS subtitles for video + caption', [
                'language' => $language,
                'caption_length' => mb_strlen($caption, 'UTF-8'),
                'caption_preview' => substr($caption, 0, 50)
            ]);

            // Create ASS subtitle file
            $assFilePath = $this->createASSFile($caption, $language, $width, $height);
            $tempFiles[] = $assFilePath; // Track for cleanup

            \Log::info('ASS file added to temp files tracking', [
                'file_path' => $assFilePath,
                'temp_files_count' => count($tempFiles)
            ]);

            // Build FFmpeg filter chain with ASS subtitles (all in one filter chain)
            $filters[] = sprintf(
                '[0:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1,subtitles=%s[v];[v]trim=duration=%d,setpts=PTS-STARTPTS,fps=%d[vout]',
                $width,
                $height,
                $width,
                $height,
                escapeshellarg($assFilePath),
                $duration,
                $fps
            );

            \Log::info('ASS subtitle filter constructed', [
                'language' => $language,
                'ass_file' => $assFilePath,
                'file_exists' => file_exists($assFilePath)
            ]);
        } elseif ($hasFlyer && !$hasCaption) {
            // Scenario 3: Video + Flyer (no caption)
            $filters[] = sprintf(
                '[0:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1[v0];[1:v]scale=%d:-1[flyer];[v0][flyer]overlay=(W-w)/2:(H-h)/2[v];[v]trim=duration=%d,setpts=PTS-STARTPTS,fps=%d[vout]',
                $width,
                $height,
                $width,
                $height,
                intval($width * 0.8),
                $duration,
                $fps
            );
        } elseif ($hasFlyer && $hasCaption) {
            // Scenario 4: Video + Flyer + Caption - USING ASS SUBTITLES
            \Log::info('Using ASS subtitles for video + flyer + caption', [
                'language' => $language,
                'caption_length' => mb_strlen($caption, 'UTF-8'),
                'caption_preview' => substr($caption, 0, 50)
            ]);

            // Create ASS subtitle file
            $assFilePath = $this->createASSFile($caption, $language, $width, $height);
            $tempFiles[] = $assFilePath; // Track for cleanup

            \Log::info('ASS file added to temp files tracking', [
                'file_path' => $assFilePath,
                'temp_files_count' => count($tempFiles)
            ]);

            // Build FFmpeg filter chain: scale video, scale flyer, overlay flyer with subtitles, then trim/fps
            $filters[] = sprintf(
                '[0:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1[v0];[1:v]scale=%d:-1[flyer];[v0][flyer]overlay=(W-w)/2:(H-h)/2,subtitles=%s[v];[v]trim=duration=%d,setpts=PTS-STARTPTS,fps=%d[vout]',
                $width,
                $height,
                $width,
                $height,
                intval($width * 0.8),
                escapeshellarg($assFilePath),
                $duration,
                $fps
            );

            \Log::info('ASS subtitle filter constructed for flyer + caption', [
                'language' => $language,
                'ass_file' => $assFilePath,
                'file_exists' => file_exists($assFilePath)
            ]);
        }
    
        // Log rendering scenario
        \Log::info('VideoRenderer scenario', [
            'has_flyer' => $hasFlyer ? 'yes' : 'no',
            'has_caption' => $hasCaption ? 'yes' : 'no',
            'flyer_path' => $flyerPath,
            'caption_length' => $hasCaption ? strlen($caption) : 0,
            'language' => $language,
            'scenario' => (!$hasFlyer && !$hasCaption) ? 'video_only' :
                         ((!$hasFlyer && $hasCaption) ? 'video_caption' :
                         (($hasFlyer && !$hasCaption) ? 'video_flyer' : 'video_flyer_caption'))
        ]);
    
        // Note: trim and fps are now included in the filter_complex chain above
        $filterComplex = implode(';', $filters);
        // Build FFmpeg command with proper stream mapping
        // FIXED: Remove quotes around %s for filter_complex and use escapeshellarg() to prevent command parsing issues
        // Build FFmpeg command like the user's example for multiple languages
        $command = sprintf(
            '%s -y %s -filter_complex %s -map "[vout]" -map 0:a? -c:v libx264 -preset fast -crf 18 -c:a aac -b:a 192k -pix_fmt yuv420p -movflags +faststart %s',
            escapeshellarg($ffmpegPath),
            implode(' ', $inputs),
            escapeshellarg($filterComplex),
            escapeshellarg($outputPath)
        );

        // Log the command for debugging FFmpeg issues
        \Log::info('FFmpeg command constructed', [
            'command_preview' => substr($command, 0, 200) . '...',
            'output_path' => $outputPath,
            'has_mp4_extension' => preg_match('/\.mp4$/i', $outputPath) ? 'YES' : 'NO'
        ]);

        \Log::info('========== FINAL FFMPEG COMMAND ==========');
        \Log::info('FFmpeg command', [
            'command_length' => strlen($command),
            'full_command' => $command
        ]);
    
        return ['command' => $command, 'tempFiles' => $tempFiles];
    }
    
    
    
    /**
     * Create ASS subtitle file for the given caption and language.
     * Returns the path to the temporary ASS file.
     */
    private function createASSFile(string $caption, string $language, int $width, int $height): string
    {
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

        // Apply improved word wrapping for all languages
        $maxCharsPerLine = $needsMaxLigatureSupport ? 25 : ($isNonLatin ? 35 : 45);
        $wrappedCaption = $this->wrapTamilText(implode("\n", $lines), $maxCharsPerLine);
        $lines = explode("\n", $wrappedCaption);
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

        $yStart = intval(($height - $totalTextHeight) / 2);
        if ($yStart < 200) {
            $yStart = 200;
        }

        // Get font file for ASS subtitles
        $fontFile = $this->getFontForLanguage($language);

        \Log::info('Creating ASS subtitle file', [
            'line_count' => $lineCount,
            'lines' => $lines,
            'y_start' => $yStart,
            'y_step' => $yStep,
            'font_file' => $fontFile,
            'language' => $language
        ]);

        // Generate ASS content
        $assContent = $this->generateASSSubtitle($lines, $fontFile, $fontSize, $yStart, $yStep, $width, $height, $language);

        // Create temporary ASS file in the same directory as other temp files
        $tempDir = storage_path('app/private/eventreel/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
            \Log::info('Created eventreel temp directory', ['path' => $tempDir]);
        }
        $assFilePath = $tempDir . '/' . Str::random(16) . '.ass';

        // Write ASS content to file with proper UTF-8 encoding
        // Add UTF-8 BOM for better compatibility with ASS subtitle readers
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM
        $utf8Content = $bom . $assContent;

        $writeResult = file_put_contents($assFilePath, $utf8Content, LOCK_EX);
        if ($writeResult === false) {
            throw new \Exception('Failed to write ASS file: ' . $assFilePath);
        }

        // Verify the file was written with correct encoding
        $writtenContent = file_get_contents($assFilePath);
        if ($writtenContent === false) {
            throw new \Exception('Failed to verify ASS file content: ' . $assFilePath);
        }

        // Check if BOM was written correctly
        if (substr($writtenContent, 0, 3) !== $bom) {
            \Log::warning('UTF-8 BOM not written correctly to ASS file', ['file' => $assFilePath]);
        }

        \Log::info('ASS file created successfully', [
            'file_path' => $assFilePath,
            'file_size' => filesize($assFilePath),
            'content_length' => strlen($assContent),
            'temp_dir_exists' => is_dir($tempDir),
            'temp_dir_writable' => is_writable($tempDir),
            'content_preview' => substr($assContent, 0, 200) . (strlen($assContent) > 200 ? '...' : '')
        ]);

        return $assFilePath;
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
        int $height,
        string $language = 'en'
    ): string {
        \Log::info('Creating ASS file', [
            'language' => $language,
            'caption_lines' => count($lines),
            'caption_preview' => substr(implode(' ', $lines), 0, 50)
        ]);

        // Get proper font family name for ASS format
        // For Tamil text, force "NotoSansTamil-Regular" (exact filename in fontsdir)
        // For other languages, use available fonts from fontsdir
        $allText = implode(' ', $lines);
        $isTamil = ($language === 'ta' || preg_match('/[\x{0B80}-\x{0BFF}]/u', $allText));

        if ($isTamil) {
            $fontName = 'NotoSansTamil-Regular'; // Exact filename for force_style
        } else {
            // For other languages, try to use available fonts
            $fontName = 'NotoSans-Regular'; // Fallback to Noto Sans
        }
        
        // ASS file header
        $ass = "[Script Info]\n";
        $ass .= "Title: Video Captions\n";
        $ass .= "ScriptType: v4.00+\n";
        $ass .= "WrapStyle: 0\n";
        $ass .= "PlayResX: {$width}\n";
        $ass .= "PlayResY: {$height}\n";
        $ass .= "ScaledBorderAndShadow: yes\n";
        $ass .= "FontProvider: fontconfig\n";  // Force fontconfig in ASS file
        $ass .= "\n";
        
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

            // Use Unicode escape sequences for complex scripts if enabled
            $useUnicodeEscapes = config('eventreel.video.use_unicode_escapes', false);

            if ($useUnicodeEscapes && $this->isComplexScript($line)) {
                $line = $this->renderTextWithUnicodeEscapes($line);
                \Log::info('Using Unicode escape sequences for complex script text', [
                    'original' => substr($line, 0, 50),
                    'escaped' => substr($this->renderTextWithUnicodeEscapes($line), 0, 50)
                ]);
            } else {
                $line = $this->escapeForASS($line);   // Escape special characters for ASS
            }

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
     * Wrap Tamil text to fit video width with proper word boundaries.
     * Tamil text needs special handling due to complex script characters.
     */
    private function wrapTamilText(string $text, int $maxCharsPerLine = 30): string
    {
        // Split by newlines first
        $lines = preg_split('/\r\n|\r|\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wrappedLines = [];

        foreach ($lines as $line) {
            $lineLength = mb_strlen($line, 'UTF-8');

            if ($lineLength > $maxCharsPerLine) {
                // For Tamil text, try word-based wrapping first, then character-based if needed
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
                            // If adding the word would exceed limit, check if current line is acceptable
                            if (mb_strlen($currentLine, 'UTF-8') > $maxCharsPerLine * 0.7) {
                                $wrappedLines[] = $currentLine;
                                $currentLine = $word;
                            } else {
                                // Try to fit more on current line or break the word
                                $remainingSpace = $maxCharsPerLine - mb_strlen($currentLine . ' ', 'UTF-8');
                                if (mb_strlen($word, 'UTF-8') <= $remainingSpace) {
                                    $currentLine = $currentLine . ' ' . $word;
                                } else {
                                    // Break long words character by character
                                    $wrappedLines[] = $currentLine;
                                    $currentLine = $this->breakWord($word, $maxCharsPerLine);
                                }
                            }
                        }
                    }
                }
                if (!empty($currentLine)) {
                    $wrappedLines[] = $currentLine;
                }
            } else {
                $wrappedLines[] = $line;
            }
        }

        // Join with newlines for drawtext
        return implode("\n", $wrappedLines);
    }

    private function breakWord(string $word, int $maxCharsPerLine): string
    {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $lines = [];
        $currentLine = '';

        foreach ($chars as $char) {
            $testLine = $currentLine . $char;
            if (mb_strlen($testLine, 'UTF-8') <= $maxCharsPerLine) {
                $currentLine = $testLine;
            } else {
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                }
                $currentLine = $char;
            }
        }

        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }

        return implode("\n", $lines);
    }

    /**
     * Escape special characters for ASS subtitle format.
     * ASS format requires escaping of commas, braces, colons, and other special characters.
     */
    private function escapeForASS(string $text): string
    {
        // For Tamil and other complex scripts, ensure proper Unicode handling
        // Convert to UTF-8 NFC (Canonical Composition) for consistent rendering
        if (function_exists('normalizer_normalize')) {
            $text = normalizer_normalize($text, \Normalizer::FORM_C);
        }

        // Escape backslash first (must be done before other escapes)
        $text = str_replace('\\', '\\\\', $text);

        // Escape ASS special characters
        $text = str_replace('{', '\\{', $text);
        $text = str_replace('}', '\\}', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(':', '\\:', $text);
        $text = str_replace('%', '%%', $text);

        return $text;
    }

    /**
     * Convert Unicode text to ASCII-safe Unicode escape sequences for ASS subtitles.
     * This can help with encoding issues in some ASS readers.
     */
    private function convertToUnicodeEscapes(string $text): string
    {
        $result = '';
        $chars = mb_str_split($text, 1, 'UTF-8');

        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');

            // Keep ASCII characters as-is for readability
            if ($code <= 127) {
                $result .= $char;
            } else {
                // Convert Unicode characters to escape sequences
                $result .= sprintf('\\u%04X', $code);
            }
        }

        return $result;
    }

    /**
     * Alternative ASS text rendering using Unicode escape sequences.
     * Use this if standard UTF-8 rendering fails.
     */
    private function renderTextWithUnicodeEscapes(string $text): string
    {
        // Normalize the text first
        if (function_exists('normalizer_normalize')) {
            $text = normalizer_normalize($text, \Normalizer::FORM_C);
        }

        // Convert to Unicode escape sequences
        $escapedText = $this->convertToUnicodeEscapes($text);

        // Apply ASS escaping
        return $this->escapeForASS($escapedText);
    }

    /**
     * Check if text contains complex script characters that may need special handling.
     */
    private function isComplexScript(string $text): bool
    {
        // Check for Tamil Unicode range (U+0B80 to U+0BFF)
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $text)) {
            return true;
        }

        // Check for other Indic scripts
        $complexRanges = [
            '/[\x{0900}-\x{097F}]/u', // Devanagari (Hindi, Marathi, Sanskrit)
            '/[\x{0980}-\x{09FF}]/u', // Bengali
            '/[\x{0A00}-\x{0A7F}]/u', // Gurmukhi (Punjabi)
            '/[\x{0A80}-\x{0AFF}]/u', // Gujarati
            '/[\x{0B00}-\x{0B7F}]/u', // Oriya
            '/[\x{0C00}-\x{0C7F}]/u', // Telugu
            '/[\x{0C80}-\x{0CFF}]/u', // Kannada
            '/[\x{0D00}-\x{0D7F}]/u', // Malayalam
            '/[\x{0D80}-\x{0DFF}]/u', // Sinhala
            '/[\x{0E00}-\x{0E7F}]/u', // Thai
            '/[\x{0E80}-\x{0EFF}]/u', // Lao
            '/[\x{0F00}-\x{0FFF}]/u', // Tibetan
            '/[\x{1000}-\x{109F}]/u', // Myanmar
            '/[\x{1780}-\x{17FF}]/u', // Khmer
            '/[\x{4E00}-\x{9FFF}]/u', // CJK Unified Ideographs (Chinese, Japanese, Korean)
            '/[\x{3040}-\x{309F}]/u', // Hiragana (Japanese)
            '/[\x{30A0}-\x{30FF}]/u', // Katakana (Japanese)
            '/[\x{AC00}-\x{D7AF}]/u', // Hangul Syllables (Korean)
            '/[\x{0600}-\x{06FF}]/u', // Arabic
            '/[\x{0750}-\x{077F}]/u', // Arabic Supplement
        ];

        foreach ($complexRanges as $range) {
            if (preg_match($range, $text)) {
                return true;
            }
        }

        return false;
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

        if ($fontPath) {
            $absoluteFontPath = realpath($fontPath);
            if ($absoluteFontPath && file_exists($absoluteFontPath)) {
                \Log::info('Using package font', [
                    'language' => $language,
                    'font_path' => $absoluteFontPath,
                    'font_size' => filesize($absoluteFontPath)
                ]);
                return $absoluteFontPath;
            } else {
                \Log::warning('Font file not found', [
                    'language' => $language,
                    'requested_path' => $fontPath,
                    'absolute_path' => $absoluteFontPath,
                    'file_exists' => file_exists($fontPath)
                ]);
            }
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
        // Skip spaces, punctuation, and control characters for better script detection
        if (preg_match('/\s|\p{P}|\p{C}/u', $char)) {
            continue;
        }

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
                 ($code >= 0x0061 && $code <= 0x007A) ||
                 ($code >= 0x0030 && $code <= 0x0039)) { // Include numbers
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

