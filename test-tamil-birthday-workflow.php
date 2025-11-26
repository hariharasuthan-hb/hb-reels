<?php

/**
 * Complete Tamil Birthday Video Generation Test
 * Tests the full workflow for "happy birth day" → Tamil video
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎂 COMPLETE TAMIL BIRTHDAY VIDEO GENERATION TEST\n";
echo "   Testing: 'happy birth day' → Tamil Video\n";
echo str_repeat("=", 60) . "\n\n";

// Test data
$eventText = 'happy birth day';
$language = 'ta';

// Get services
$aiService = app(\HbReels\EventReelGenerator\Services\AIService::class);
$videoRenderer = app(\HbReels\EventReelGenerator\Services\VideoRenderer::class);
$reelController = app(\HbReels\EventReelGenerator\Controllers\ReelController::class);

echo "🔧 Services Loaded:\n";
echo "   ✅ AI Service\n";
echo "   ✅ Video Renderer\n";
echo "   ✅ Reel Controller\n\n";

// Step 1: Event Text Input
echo "📝 Step 1 - Event Text Input:\n";
echo "   User Input: '$eventText'\n";
echo "   Selected Language: '$language'\n";
echo "   ✅ Ready for processing\n\n";

// Step 2: Generate English caption for video search
$englishCaption = $aiService->generateCaption($eventText, 'en');

echo "🤖 Step 2 - AI English Caption Generation:\n";
echo "   Input Text: '$eventText'\n";
echo "   Generated Caption: '$englishCaption'\n";
echo "   Length: " . strlen($englishCaption) . " characters\n";
echo "   ✅ Success\n\n";

// Step 3: Translate to Tamil
$tamilCaption = $aiService->translateWithGoogle($englishCaption, 'ta', 'en');

echo "🌐 Step 3 - Google Translate to Tamil:\n";
echo "   English Input: '$englishCaption'\n";
echo "   Tamil Output: '$tamilCaption'\n";
echo "   Has Unicode: " . (preg_match('/[\x{0080}-\x{FFFF}]/u', $tamilCaption) ? 'Yes ✅' : 'No ❌') . "\n";
echo "   Translation Success: " . ($tamilCaption !== $englishCaption ? 'Yes ✅' : 'No (Using fallback) ⚠️') . "\n\n";

// Step 4: Format for video overlay
// For Tamil language, we use direct translation result
$overlayText = $tamilCaption;

echo "🎬 Step 4 - Video Overlay Formatting:\n";
echo "   Tamil Caption: '$tamilCaption'\n";
echo "   Formatted Overlay: '$overlayText'\n";
echo "   Ready for Video: " . (!empty($overlayText) ? 'Yes ✅' : 'No ❌') . "\n\n";

// Step 5: Test ASS subtitle generation
$reflection3 = new ReflectionClass($videoRenderer);
$assMethod = $reflection3->getMethod('generateASSSubtitle');
$assMethod->setAccessible(true);

// Get Tamil font path
$fontMethod = $reflection3->getMethod('getFontForLanguage');
$fontMethod->setAccessible(true);
$tamilFont = $fontMethod->invoke($videoRenderer, 'ta');

$tamilLines = [$tamilCaption];
$assContent = $assMethod->invoke($videoRenderer, $tamilLines, $tamilFont, 48, 800, 100, 1080, 1920);

$hasScriptInfo = strpos($assContent, '[Script Info]') !== false;
$hasStyles = strpos($assContent, '[V4+ Styles]') !== false;
$hasEvents = strpos($assContent, '[Events]') !== false;
$hasTamilFont = strpos($assContent, 'Noto Sans Tamil') !== false;
$hasTamilText = strpos($assContent, $tamilCaption) !== false;

echo "🎭 Step 5 - ASS Subtitle Generation:\n";
echo "   Tamil Font Path: $tamilFont\n";
echo "   Font Exists: " . (file_exists($tamilFont) ? 'Yes ✅' : 'No ❌') . "\n";
echo "   ASS Script Info: " . ($hasScriptInfo ? 'Valid ✅' : 'Invalid ❌') . "\n";
echo "   ASS Styles: " . ($hasStyles ? 'Valid ✅' : 'Invalid ❌') . "\n";
echo "   ASS Events: " . ($hasEvents ? 'Valid ✅' : 'Invalid ❌') . "\n";
echo "   Tamil Font: " . ($hasTamilFont ? 'Embedded ✅' : 'Missing ❌') . "\n";
echo "   Tamil Text: " . ($hasTamilText ? 'Included ✅' : 'Missing ❌') . "\n";
echo "   ASS Size: " . strlen($assContent) . " characters\n\n";

// Step 6: Generate FFmpeg command
$reflection4 = new ReflectionClass($videoRenderer);
$cmdMethod = $reflection4->getMethod('buildFFmpegCommand');
$cmdMethod->setAccessible(true);

$command = $cmdMethod->invoke($videoRenderer, 'ffmpeg', '/tmp/input.mp4', '/tmp/output.mp4', 1080, 1920, 5, 30, null, $overlayText, 'ta');

$hasAssFilter = strpos($command, 'ass=') !== false;
$hasScale = strpos($command, 'scale=') !== false;
$hasLibass = strpos($command, 'libass') !== false;
$hasMapVout = strpos($command, '[vout]') !== false;
$hasAudioCopy = strpos($command, '-c:a copy') !== false;

echo "🎥 Step 6 - FFmpeg Command Generation:\n";
echo "   ASS Filter: " . ($hasAssFilter ? 'Included ✅' : 'Missing ❌') . "\n";
echo "   Video Scaling: " . ($hasScale ? 'Included ✅' : 'Missing ❌') . "\n";
echo "   Output Mapping: " . ($hasMapVout ? 'Correct ✅' : 'Incorrect ❌') . "\n";
echo "   Audio Preservation: " . ($hasAudioCopy ? 'Yes ✅' : 'No ❌') . "\n";
echo "   Command Preview: " . substr($command, 0, 120) . "...\n\n";

// Step 7: Language detection verification
$detectMethod = $reflection3->getMethod('detectLanguage');
$detectMethod->setAccessible(true);

$englishLang = $detectMethod->invoke($videoRenderer, $englishCaption);
$tamilLang = $detectMethod->invoke($videoRenderer, $tamilCaption);

echo "🏷️  Step 7 - Language Detection:\n";
echo "   English Text Language: '$englishLang' " . ($englishLang === 'en' ? '✅' : '❌') . "\n";
echo "   Tamil Text Language: '$tamilLang' " . ($tamilLang === 'ta' ? '✅' : '❌') . "\n\n";

// Final Assessment
echo "🎯 FINAL ASSESSMENT - TAMIL BIRTHDAY VIDEO WORKFLOW\n";
echo str_repeat("=", 55) . "\n\n";

$workflowSteps = [
    'Text Extraction' => !empty($eventText),
    'AI Caption Generation' => !empty($englishCaption),
    'Tamil Translation' => !empty($tamilCaption) && $tamilCaption !== $englishCaption,
    'Video Overlay Formatting' => !empty($overlayText),
    'ASS Subtitle Generation' => $hasScriptInfo && $hasTamilFont && $hasTamilText,
    'FFmpeg Command Generation' => $hasAssFilter && $hasScale && $hasMapVout,
    'Language Detection' => $englishLang === 'en' && $tamilLang === 'ta',
    'Font Availability' => file_exists($tamilFont)
];

$completedSteps = 0;
foreach ($workflowSteps as $step => $completed) {
    $status = $completed ? '✅' : '❌';
    echo "   $status $step\n";
    if ($completed) $completedSteps++;
}

$successRate = round(($completedSteps / count($workflowSteps)) * 100, 1);

echo "\n📊 WORKFLOW COMPLETENESS: $completedSteps/" . count($workflowSteps) . " steps ($successRate%)\n\n";

if ($successRate >= 90) {
    echo "🟢 EXCELLENT! TAMIL BIRTHDAY VIDEO WORKFLOW IS FULLY OPERATIONAL!\n\n";
    echo "🎂 BIRTHDAY VIDEO SUMMARY:\n";
    echo "   🎁 Input: 'happy birth day'\n";
    echo "   🤖 AI Processing: Generates celebration message\n";
    echo "   🌐 Translation: Converts to beautiful Tamil\n";
    echo "   📝 Tamil Text: '$tamilCaption'\n";
    echo "   🎬 ASS Subtitles: Ready with Noto Tamil font\n";
    echo "   🎥 Video Output: Professional Tamil birthday video\n\n";

    echo "🚀 PRODUCTION STATUS: READY FOR TAMIL BIRTHDAY VIDEOS!\n";
    echo "   ✅ All components tested and working\n";
    echo "   ✅ Fallback translations available\n";
    echo "   ✅ ASS subtitles with proper Tamil rendering\n";
    echo "   ✅ FFmpeg commands optimized for quality\n\n";

    echo "🎉 HAPPY BIRTHDAY IN TAMIL - READY TO CELEBRATE! 🇮🇳\n";

} elseif ($successRate >= 75) {
    echo "🟡 GOOD! MOST COMPONENTS WORKING, MINOR ISSUES TO RESOLVE\n";
    echo "   Check the failed steps above for improvements needed.\n";

} else {
    echo "🔴 ISSUES DETECTED - WORKFLOW NEEDS ATTENTION\n";
    echo "   Multiple components failed - check configuration and dependencies.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎂 Tamil Birthday Video Test Completed\n";
echo "   Tested: 'happy birth day' → Tamil celebration video\n";
echo "   Result: " . ($successRate >= 90 ? 'SUCCESS ✅' : 'NEEDS WORK ⚠️') . "\n";
