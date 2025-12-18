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

        // Resolve flyer path and verify it exists
        $flyerFullPath = null;
        if ($flyerPath) {
            $flyerFullPath = strpos($flyerPath, $diskRoot) === 0 
                ? $flyerPath 
                : Storage::disk($disk)->path($flyerPath);
            
            // Verify flyer file exists before using it
            if (!file_exists($flyerFullPath)) {
                \Log::warning('Flyer file not found, skipping flyer overlay', [
                    'flyer_path' => $flyerPath,
                    'full_path' => $flyerFullPath
                ]);
                $flyerFullPath = null; // Skip flyer if file doesn't exist
            }
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
            $flyerFullPath, // Pass resolved flyer path (or null if doesn't exist)
            $caption,
            $language
        );

        // DEBUG: Log the actual FFmpeg command being executed (for troubleshooting)
        \Log::warning('FFMPEG COMMAND DEBUG', [
            'command' => $command,
            'command_length' => strlen($command)
        ]);
        
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
    
        // Track whether flyer was actually added to filter chain
        $flyerAdded = false;
        
        // Input 2: Flyer (if provided and file exists)
        if ($flyerPath && file_exists($flyerPath)) {
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
            $flyerAdded = true;
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

            // Calculate available width for text
            // If flyer exists, text should fit within flyer area accounting for border/padding
            // Flyer is 80% of video width, but we need to account for border (typically 15-20% on each side)
            // So text should use ~50% of video width to stay well within flyer border
            // If no flyer, use full width minus side margins (10% on each side = 80% usable)
            $availableWidth = $flyerAdded 
                ? intval($width * 0.50)  // Flyer is 80% width, text uses 50% to stay well inside flyer border (15% margin on each side)
                : intval($width * 0.85);  // No flyer: use 85% of width (7.5% margin on each side)
            
            // Estimate characters per line based on available width and font size
            // Average character width varies by language and font size
            // For Latin: ~0.6 * fontSize, for complex scripts: ~0.8 * fontSize
            $avgCharWidth = $needsMaxLigatureSupport 
                ? intval(56 * 0.8)  // Complex scripts: wider characters
                : ($isNonLatin 
                    ? intval(56 * 0.7)  // Non-Latin but simpler: medium width
                    : intval(56 * 0.6)); // Latin: narrower characters
            
            // Calculate max chars based on available width
            $calculatedMaxChars = max(15, intval($availableWidth / $avgCharWidth));
            
            // Apply language-specific limits (minimums for readability)
            $languageMinChars = $needsMaxLigatureSupport ? 18 : ($isNonLatin ? 25 : 30);
            $languageMaxChars = $needsMaxLigatureSupport ? 25 : ($isNonLatin ? 35 : 45);
            
            // Use calculated value but respect language limits
            $maxCharsPerLine = max($languageMinChars, min($calculatedMaxChars, $languageMaxChars));

            $wrappedLines = [];
            foreach ($lines as $line) {
                $lineLength = mb_strlen($line, 'UTF-8');

                if ($lineLength > $maxCharsPerLine) {
                    // Split by whitespace (handles multiple spaces, tabs, etc.)
                    $words = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
                    $currentLine = '';

                    foreach ($words as $word) {
                        $wordLength = mb_strlen($word, 'UTF-8');
                        
                        // Handle very long words that exceed the limit
                        if ($wordLength > $maxCharsPerLine) {
                            // First, save current line if it has content
                            if ($currentLine) {
                                $wrappedLines[] = $currentLine;
                                $currentLine = '';
                            }
                            
                            // Break the long word into chunks
                            $remainingWord = $word;
                            while (mb_strlen($remainingWord, 'UTF-8') > $maxCharsPerLine) {
                                // Break at 80% of max length for better readability
                                $breakPoint = intval($maxCharsPerLine * 0.8);
                                $chunk = mb_substr($remainingWord, 0, $breakPoint, 'UTF-8');
                                $wrappedLines[] = $chunk;
                                $remainingWord = mb_substr($remainingWord, $breakPoint, null, 'UTF-8');
                            }
                            
                            // Add remaining part as current line
                            if ($remainingWord) {
                                $currentLine = $remainingWord;
                            }
                        } else {
                            // Normal word processing
                            if (empty($currentLine)) {
                                $currentLine = $word;
                            } else {
                                $testLine = $currentLine . ' ' . $word;
                                $testLength = mb_strlen($testLine, 'UTF-8');
                                
                                if ($testLength <= $maxCharsPerLine) {
                                    $currentLine = $testLine;
                                } else {
                                    // Current line is full, start a new line
                                    $wrappedLines[] = $currentLine;
                                    $currentLine = $word;
                                }
                            }
                        }
                    }
                    
                    // Add the last line if it has content
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

            // Calculate Y position for text
            // If flyer exists, position text within flyer area accounting for border
            // If no flyer, position in lower third of video (standard caption position)
            if ($flyerAdded) {
                // Flyer is centered, so calculate text position within flyer bounds
                // Flyer height is approximately 80% of video height when scaled
                $flyerHeight = intval($height * 0.8);
                $flyerTop = intval(($height - $flyerHeight) / 2);
                $flyerBottom = $flyerTop + $flyerHeight;
                
                // Account for flyer border/padding (typically 15-20% of flyer dimensions)
                // This ensures text stays well inside the flyer border
                $flyerBorderPadding = intval($flyerHeight * 0.15); // 15% border/padding to ensure text stays inside border
                $flyerInnerTop = $flyerTop + $flyerBorderPadding;
                $flyerInnerBottom = $flyerBottom - $flyerBorderPadding;
                $flyerInnerHeight = $flyerInnerBottom - $flyerInnerTop;
                
                // Also account for horizontal border (width-wise)
                $flyerWidth = intval($width * 0.8); // Flyer is 80% of video width
                $flyerBorderPaddingHorizontal = intval($flyerWidth * 0.15); // 15% border on each side
                
                // Calculate available height for text (with margins)
                $textTopMargin = 100; // Margin from top of inner flyer area
                $textBottomMargin = 10; // Margin from bottom of inner flyer area
                $maxAvailableHeight = $flyerInnerHeight - $textTopMargin - $textBottomMargin;
                
                // Check if text fits, if not, adjust font size and spacing
                $initialTotalHeight = ($lineCount * $yStep);
                if ($initialTotalHeight > $maxAvailableHeight && $lineCount > 0) {
                    // Text is too tall, reduce spacing and font size proportionally
                    $yStep = intval($maxAvailableHeight / $lineCount);
                    $totalTextHeight = ($lineCount * $yStep);
                    
                    // Reduce font size proportionally to maintain readability
                    $heightRatio = $maxAvailableHeight / max($initialTotalHeight, 1);
                    $fontSize = max(32, intval($fontSize * $heightRatio)); // Minimum 32px
                } else {
                    $totalTextHeight = $initialTotalHeight;
                }
                
                // Position text in the flyer inner area (moved down more)
                // Start from the very top of inner flyer area
                $textAreaTop = $flyerInnerTop; // Start from top of inner flyer area
                $textAreaHeight = intval($flyerInnerHeight * 0.50); // Use top 50% of inner area
                
                // Position text further down from top
                $yStart = $textAreaTop + 80; // 80px margin from very top (moved down more)
                
                // Ensure Y position is aligned to avoid sub-pixel rendering issues
                $yStart = intval($yStart);
                
                // CRITICAL: Ensure text stays well inside flyer border boundaries
                // Calculate strict boundaries with additional safety margin
                $safetyMargin = 20; // Extra safety margin to ensure text never touches border
                $strictInnerTop = $flyerInnerTop + $safetyMargin;
                $strictInnerBottom = $flyerInnerBottom - $safetyMargin;
                
                // Ensure text doesn't go below flyer inner area (respecting border with safety margin)
                $maxY = $strictInnerBottom - $textBottomMargin;
                if (($yStart + $totalTextHeight) > $maxY) {
                    $yStart = max($strictInnerTop, $maxY - $totalTextHeight);
                }
                
                // Ensure text doesn't go above flyer inner area (with safety margin)
                $minY = $strictInnerTop + 80; // Margin from top
                if ($yStart < $minY) {
                    $yStart = $minY;
                }
                
                // Final validation: ensure all text fits within strict flyer inner bounds
                if (($yStart + $totalTextHeight) > $strictInnerBottom - $textBottomMargin) {
                    // Last resort: reduce font size and spacing to fit within border
                    $availableHeight = $strictInnerBottom - $strictInnerTop - 80 - $textBottomMargin;
                    if ($lineCount > 0 && $availableHeight > 0) {
                        $yStep = intval($availableHeight / $lineCount);
                        $totalTextHeight = ($lineCount * $yStep);
                        $heightRatio = $availableHeight / max($initialTotalHeight, 1);
                        $fontSize = max(28, intval($fontSize * $heightRatio)); // Minimum 28px
                        $yStart = $strictInnerTop + 80;
                    }
                }
                
                // Double-check: text must never exceed strict boundaries
                if ($yStart < $strictInnerTop || ($yStart + $totalTextHeight) > $strictInnerBottom) {
                    // Emergency fallback: center within strict boundaries
                    $yStart = $strictInnerTop + intval(($strictInnerBottom - $strictInnerTop - $totalTextHeight) / 2);
                    $yStart = max($strictInnerTop, min($yStart, $strictInnerBottom - $totalTextHeight));
                }
            } else {
                // No flyer: center text vertically on screen
                $totalTextHeight = ($lineCount * $yStep);
                $maxHeight = $height - 200; // Leave 100px margin top and bottom

                if ($totalTextHeight > $maxHeight && $lineCount > 0) {
                    $yStep = intval($maxHeight / $lineCount);
                    $totalTextHeight = ($lineCount * $yStep);
                    // Reduce font size proportionally
                    $heightRatio = $maxHeight / ($lineCount * 100); // Original yStep was ~100
                    $fontSize = max(32, intval($fontSize * $heightRatio));
                }
                // Center text vertically on screen with proper alignment
                $yStart = intval(($height - $totalTextHeight) / 2);
                // Ensure minimum margin from top
                if ($yStart < 100) {
                    $yStart = 100;
                }
                // Ensure Y position is properly aligned (no sub-pixel rendering)
                $yStart = intval($yStart);
            }

            // QUICKTIME COMPATIBILITY FIX: Use drawtext instead of ASS subtitles
            // ASS subtitles work great in VLC/MPC but QuickTime has limited subtitle support
            $fontFile = $this->getFontForLanguage($language);
            $currentY = $yStart;

            // Filter out empty lines first to get accurate count
            $nonEmptyLines = array_filter($lines, function($line) {
                return trim($line) !== '';
            });
            $nonEmptyLines = array_values($nonEmptyLines); // Re-index array
            $totalNonEmptyLines = count($nonEmptyLines);

            $processedLineIndex = 0;
            $lastTextLabel = '[v]'; // Initialize - will be updated in loop if captions exist
            
            // Only process if we have non-empty lines
            if ($totalNonEmptyLines > 0) {
                // Process only non-empty lines
                foreach ($nonEmptyLines as $line) {
                    // Escape special characters for FFmpeg drawtext filter text parameter
                    // CRITICAL: Must escape commas, colons, quotes, and backslashes
                    // We use SINGLE QUOTES for text parameter to avoid quote escaping issues
                    $safe = $this->escapeDrawtext($line);
                    
                    // Handle newlines - convert to space (FFmpeg drawtext doesn't support newlines in text parameter)
                    $safe = str_replace(["\r\n", "\r", "\n"], ' ', $safe);
                    
                    // Remove any control characters that might cause issues
                    $safe = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $safe);
                    
                    // Clean up multiple spaces
                    $safe = preg_replace('/\s+/', ' ', trim($safe));

                    // Wrap long text manually (FFmpeg doesn't have auto text wrapping)
                    // Use higher limit for Unicode text (Tamil, etc.) since characters take more bytes
                    $wrapLimit = in_array($language, ['ta', 'hi', 'te', 'ml', 'kn', 'bn', 'gu', 'pa', 'or', 'mr', 'th', 'my', 'km', 'lo', 'zh', 'ja', 'ko', 'ar', 'fa', 'ur'])
                        ? 25 // Lower limit for complex scripts
                        : 35; // Standard limit for Latin scripts
                    $safe = $this->wrapText($safe, $wrapLimit);

                    // Create unique stream labels for each line to chain them properly
                    // CRITICAL: Never use [vout] as output - only trim filter can output [vout]
                    // IMPORTANT: Don't reuse [v] label - use unique labels for all filters
                    $inputLabel = $processedLineIndex === 0 ? '[v]' : "[v{$processedLineIndex}]";
                    $isLastLine = $processedLineIndex === $totalNonEmptyLines - 1;
                    // Use a unique intermediate label for output - NEVER use [vout] here
                    // All drawtext filters output intermediate labels, only trim outputs [vout]
                    $outputLabel = $isLastLine ? "[vtext{$processedLineIndex}]" : "[v" . ($processedLineIndex + 1) . "]";
                    
                    // Track the last output label for use in trim filter
                    $lastTextLabel = $outputLabel;

                    // Draw text centered on screen with dark semi-transparent background box
                    // Use SINGLE QUOTES for text parameter - this is the safest approach
                    // Text is already escaped by escapeDrawtext() function
                    // Text is perfectly centered horizontally using (w-text_w)/2
                    // Dark black overlay (box) for maximum readability on all color images
                    // Ensure Y position is properly aligned (no sub-pixel rendering)
                    $alignedY = intval($currentY);
                    
                    // Use fully opaque black background for maximum caption visibility
                    // FFmpeg drawtext boxcolor format: 0xRRGGBB@alpha where alpha is 0.0-1.0
                    // 0x000000@1.0 = fully opaque black, or use 0x000000FF for 8-bit alpha
                    // Increasing boxborderw to 15 for better padding around text
                    if ($fontFile && file_exists($fontFile)) {
                        $filters[] = sprintf(
                            "%sdrawtext=fontfile='%s':text='%s':fontsize=%d:fontcolor=white:" .
                            "x=(w-text_w)/2:y=%d:box=1:boxcolor=0x000000@1.0:boxborderw=15%s",
                            $inputLabel,
                            $fontFile, // Font file path in single quotes
                            $safe,      // Text in single quotes (already escaped)
                            $fontSize,
                            $alignedY,  // Use aligned Y position
                            $outputLabel
                        );
                    } else {
                        // Fallback to system font with fully opaque black background
                        $filters[] = sprintf(
                            "%sdrawtext=font='Arial':text='%s':fontsize=%d:fontcolor=white:" .
                            "x=(w-text_w)/2:y=%d:box=1:boxcolor=0x000000@1.0:boxborderw=15%s",
                            $inputLabel,
                            $safe,      // Text in single quotes (already escaped)
                            $fontSize,
                            $alignedY,  // Use aligned Y position
                            $outputLabel
                        );
                    }

                    $currentY += $yStep;
                    $processedLineIndex++;
                }
            }
            // If no caption or all lines were empty, $lastTextLabel remains '[v]'
        } else {
            // No caption provided - use [v] from video/flyer processing
            $lastTextLabel = '[v]';
        }
        
        // Ensure lastTextLabel is always set before using it
        if (empty($lastTextLabel)) {
            $lastTextLabel = '[v]';
        }
        
        // CRITICAL: Verify that lastTextLabel exists in the filter chain before using it
        // Build a temporary filter complex to check if the label exists as an output
        $tempFilterComplex = implode(';', $filters);
        $labelPattern = preg_quote($lastTextLabel, '/');
        
        // Check if lastTextLabel appears as an output (ends with [label])
        // Pattern: ...something[label]; or ...something[label] at end
        $labelExists = preg_match('/' . $labelPattern . '(;|$)/', $tempFilterComplex);
        
        // If label doesn't exist, fall back to [v] which is always created
        if (!$labelExists) {
            \Log::warning('lastTextLabel not found in filter chain, falling back to [v]', [
                'last_text_label' => $lastTextLabel,
                'filter_complex' => $tempFilterComplex,
                'filters' => $filters
            ]);
            $lastTextLabel = '[v]';
        }
        
        // CRITICAL: Only the trim filter can output [vout]
        // [vout] must appear ONLY ONCE and ONLY as the final output
        // Never use [vout] as input - it's the final destination
        // Format: [input_label]trim=duration=X,setpts=PTS-STARTPTS,fps=Y[vout]
        $filters[] = $lastTextLabel . 'trim=duration=' . $duration . ',setpts=PTS-STARTPTS,fps=' . $fps . '[vout]';
    
        // CRITICAL: Ensure we have at least one filter (video scaling)
        if (empty($filters)) {
            throw new \Exception('No filters generated - filter chain is empty');
        }
        
        $filterComplex = implode(';', $filters);
        
        // DEBUG: Verify [vout] appears only once and is the final output
        $voutCount = substr_count($filterComplex, '[vout]');
        if ($voutCount !== 1) {
            \Log::error('FFMPEG FILTER GRAPH ERROR: [vout] appears ' . $voutCount . ' times (must be exactly 1)', [
                'filter_complex' => $filterComplex,
                'last_text_label' => $lastTextLabel,
                'vout_positions' => strpos($filterComplex, '[vout]'),
                'filters' => $filters
            ]);
            throw new \Exception('Invalid filter graph: [vout] must appear exactly once. Found: ' . $voutCount);
        }
        
        // Verify lastTextLabel exists in filter chain
        if (!empty($lastTextLabel) && strpos($filterComplex, $lastTextLabel) === false) {
            \Log::error('FFMPEG FILTER GRAPH ERROR: lastTextLabel not found in filter chain', [
                'last_text_label' => $lastTextLabel,
                'filter_complex' => $filterComplex,
                'filters' => $filters
            ]);
            throw new \Exception('Invalid filter graph: lastTextLabel "' . $lastTextLabel . '" not found in filter chain');
        }
        
        // DEBUG: Log filter graph for troubleshooting (use warning level so it's always logged)
        \Log::warning('FFMPEG FILTER GRAPH DEBUG', [
            'filter_complex' => $filterComplex,
            'last_text_label' => $lastTextLabel,
            'vout_count' => $voutCount,
            'has_flyer' => $flyerAdded,
            'has_caption' => !empty($caption),
            'filter_count' => count($filters),
            'filters_array' => $filters
        ]);
    
        // Escape the filter_complex for shell execution
        // CRITICAL FIX: escapeshellarg() can break FFmpeg filter_complex parsing
        // Instead, manually escape single quotes for shell safety while preserving FFmpeg syntax
        // Wrap in single quotes and escape internal single quotes as '\''
        $escapedFilterComplex = str_replace("'", "'\\''", $filterComplex);
        $escapedFilterComplex = "'" . $escapedFilterComplex . "'";
        
        $command = sprintf(
            '%s %s -filter_complex %s -map "[vout]" -t %d -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -movflags +faststart %s',
            escapeshellarg($ffmpegPath),
            implode(' ', $inputs),
            $escapedFilterComplex, // Use manually escaped version
            $duration,
            escapeshellarg($outputPath)
        );
    
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
        // - Dark semi-transparent background box (&HD0000000) for maximum readability on all color images
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
            "Style: Default,%s,%d,&H00FFFFFF,&H000000FF,&H00000000,&HFF000000,-1,0,0,0,100,100,5,0,3,5.0,4,5,30,30,30,1\n\n",
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
            return $foundFonts['exact'];
        }
        
        // 2. Regular font (preferred for all languages)
        if ($foundFonts['regular']) {
            return $foundFonts['regular'];
        }
        
        // 3. Medium font (fallback)
        if ($foundFonts['medium']) {
            return $foundFonts['medium'];
        }
        
        // 4. Bold font (last resort fallback)
        if ($foundFonts['bold']) {
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
     * Escape special characters for FFmpeg drawtext filter text parameter.
     * CRITICAL: Must escape commas, colons, quotes, and backslashes.
     * 
     * @param string $text The text to escape
     * @return string Escaped text safe for FFmpeg drawtext
     */
    /**
     * Escape special characters for FFmpeg drawtext filter.
     * CRITICAL: Must escape commas, colons, single quotes, double quotes, and backslashes.
     * We use SINGLE QUOTES for text parameter, so single quotes must be escaped as \'
     * 
     * @param string $text Text to escape
     * @return string Escaped text safe for FFmpeg drawtext filter
     */
    private function escapeDrawtext(string $text): string
    {
        // Escape in this order to avoid double-escaping:
        // 1. Backslashes first (so they don't interfere with other escaping)
        // 2. Single quotes (escape as \')
        // 3. Double quotes (escape as \")
        // 4. Colons (escape as \:) - they are parameter separators
        // 5. Commas (escape as \,) - they are filter separators in filter_complex (CRITICAL!)
        
        $escaped = $text;
        $escaped = str_replace('\\', '\\\\', $escaped);  // Escape backslashes first
        $escaped = str_replace("'", "\\'", $escaped);     // Escape single quotes
        $escaped = str_replace('"', '\\"', $escaped);     // Escape double quotes
        $escaped = str_replace(':', '\\:', $escaped);      // Escape colons
        $escaped = str_replace(',', '\\,', $escaped);      // Escape commas (CRITICAL!)
        
        return $escaped;
    }

    /**
     * Wrap text at a specified character width for FFmpeg drawtext.
     * Uses proper Unicode character counting for multilingual text.
     * Properly handles word boundaries and long words.
     */
    private function wrapText(string $text, int $maxChars): string
    {
        // Remove any existing pipe characters (from previous processing) and split by them first
        $text = str_replace('|', ' ', $text);
        $text = trim($text);
        
        // Use mb_strlen for proper Unicode character counting
        if (mb_strlen($text, 'UTF-8') <= $maxChars) {
            return $text;
        }

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $wordLength = mb_strlen($word, 'UTF-8');
            
            // If a single word is longer than maxChars, we need to break it
            if ($wordLength > $maxChars) {
                // First, add current line if it has content
                if ($currentLine) {
                    $lines[] = $currentLine;
                    $currentLine = '';
                }
                
                // Break the long word into chunks
                $remainingWord = $word;
                while (mb_strlen($remainingWord, 'UTF-8') > $maxChars) {
                    // Try to break at a reasonable point (prefer breaking at 80% of max length)
                    $breakPoint = intval($maxChars * 0.8);
                    $chunk = mb_substr($remainingWord, 0, $breakPoint, 'UTF-8');
                    $lines[] = $chunk;
                    $remainingWord = mb_substr($remainingWord, $breakPoint, null, 'UTF-8');
                }
                
                // Add remaining part of the word as current line
                if ($remainingWord) {
                    $currentLine = $remainingWord;
                }
            } else {
                // Normal word that fits within limit
                if (empty($currentLine)) {
                    $currentLine = $word;
                } else {
                    $testLine = $currentLine . ' ' . $word;
                    $testLength = mb_strlen($testLine, 'UTF-8');
                    
                    if ($testLength <= $maxChars) {
                        $currentLine = $testLine;
                    } else {
                        // Current line is full, start a new line
                        $lines[] = $currentLine;
                        $currentLine = $word;
                    }
                }
            }
        }

        // Add the last line if it has content
        if ($currentLine) {
            $lines[] = $currentLine;
        }

        // Use pipe character for line breaks (FFmpeg drawtext compatible)
        return implode('|', $lines);
    }
}

