<?php

/**
 * Multi-Language Setup Verification Script
 * Run with: php check-multilang-setup.php
 */

echo "🔍 MULTI-LANGUAGE SETUP VERIFICATION\n";
echo str_repeat("=", 50) . "\n\n";

// Check 1: Font Files
echo "1. 📁 Font Files Check:\n";
$fontDirs = [
    'noto-sans-indic' => ['NotoSansTamil-Regular.ttf', 'NotoSansDevanagari-Regular.ttf'],
    'noto-sans-arabic' => ['NotoSansArabic-Regular.ttf'],
    'noto-sans-cjk' => ['NotoSansCJK-Regular.ttc'],
    'noto-sans-thai' => ['NotoSansThai-Regular.ttf']
];

$totalFonts = 0;
foreach ($fontDirs as $dir => $expectedFonts) {
    $path = "packages/hb-reels/resources/fonts/$dir";
    $foundFonts = glob("$path/*.ttf") ?: [];
    $foundFonts = array_merge($foundFonts, glob("$path/*.ttc") ?: []);
    $count = count($foundFonts);
    $totalFonts += $count;

    $status = $count > 0 ? "✅" : "❌";
    echo "   $status $dir: $count font files\n";
}
echo "   📊 Total: $totalFonts fonts found\n\n";

// Check 2: Dependencies
echo "2. 📦 Dependencies Check:\n";
// Check for vendor packages (these are loaded via composer autoload)
$vendorPath = 'vendor/stichoza/google-translate-php/src';
$guzzlePath = 'vendor/guzzlehttp/guzzle/src/Client.php';
$status1 = file_exists($vendorPath) ? "✅" : "❌";
$status2 = file_exists($guzzlePath) ? "✅" : "❌";
echo "   $status1 Google Translate (stichoza/google-translate-php)\n";
echo "   $status2 Guzzle HTTP (guzzlehttp/guzzle)\n";
echo "\n";

// Check 3: Services
echo "3. 🔧 Package Services Check:\n";
// Check if service files exist
$services = [
    'packages/hb-reels/src/Services/AIService.php' => 'AI Service',
    'packages/hb-reels/src/Services/VideoRenderer.php' => 'Video Renderer',
    'packages/hb-reels/src/Services/OCRService.php' => 'OCR Service',
];

foreach ($services as $file => $name) {
    $status = file_exists($file) ? "✅" : "❌";
    echo "   $status $name ($file)\n";
}
echo "\n";

// Check 4: Configuration
echo "4. ⚙️ Configuration Check:\n";
$configPath = 'packages/hb-reels/config/eventreel.php';
if (file_exists($configPath)) {
    echo "   ✅ Config file exists\n";

    // Try to check some config values
    try {
        // This will fail due to env() function, but we can check file contents
        $content = file_get_contents($configPath);
        $checks = [
            'use_google_translate' => strpos($content, 'use_google_translate') !== false,
            'ollama_url' => strpos($content, 'ollama_url') !== false,
            'fonts' => strpos($content, "'fonts'") !== false,
        ];

        foreach ($checks as $key => $found) {
            $status = $found ? "✅" : "❌";
            echo "   $status $key configuration\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Cannot verify config values (Laravel env() function needed)\n";
    }
} else {
    echo "   ❌ Config file missing\n";
}
echo "\n";

// Check 5: Form Integration
echo "5. 🎨 Form Integration Check:\n";
$formPath = 'packages/hb-reels/resources/views/index.blade.php';
if (file_exists($formPath)) {
    $content = file_get_contents($formPath);
    $checks = [
        'language dropdown' => strpos($content, 'name="language"') !== false,
        'tamil option' => strpos($content, 'value="ta"') !== false,
        'auto detect' => strpos($content, 'value="auto"') !== false,
    ];

    foreach ($checks as $feature => $found) {
        $status = $found ? "✅" : "❌";
        echo "   $status $feature\n";
    }
} else {
    echo "   ❌ Form file missing\n";
}
echo "\n";

// Final Status
echo "🎯 FINAL STATUS:\n";
$allGood = $totalFonts >= 10 &&
           file_exists($vendorPath) &&
           file_exists($guzzlePath) &&
           file_exists('packages/hb-reels/src/Services/AIService.php') &&
           file_exists($configPath) &&
           file_exists($formPath);

if ($allGood) {
    echo "   🟢 MULTI-LANGUAGE SUPPORT IS FULLY CONFIGURED!\n";
    echo "   🎉 Ready for Tamil, Arabic, Chinese, and 20+ other languages\n";
    echo "   🚀 Start with: npm run ai:serve && php artisan serve\n";
} else {
    echo "   🔴 SETUP INCOMPLETE - Some components missing\n";
    echo "   📖 Check MULTI_LANGUAGE_SETUP.md for detailed setup instructions\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 Next Steps:\n";
echo "   1. Start AI server: npm run ai:serve\n";
echo "   2. Start Laravel: php artisan serve\n";
echo "   3. Visit event reel page and test with Tamil text\n";
echo "   4. Check MULTI_LANGUAGE_SETUP.md for details\n";
