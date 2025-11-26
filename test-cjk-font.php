<?php

require_once __DIR__ . '/vendor/autoload.php';

// Test CJK font loading
echo "=== CJK Font Loading Test ===\n\n";

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use HbReels\EventReelGenerator\Services\VideoRenderer;

// Test complex script languages
$complexScriptTests = [
    'zh' => '你好世界', // Chinese
    'ja' => 'こんにちは世界', // Japanese
    'ko' => '안녕하세요 세계', // Korean
    'ar' => 'مرحبا بالعالم', // Arabic
    'th' => 'สวัสดีโลก', // Thai
    'fa' => 'سلام دنیا', // Persian
];

$renderer = new VideoRenderer();

foreach ($complexScriptTests as $lang => $text) {
    echo "Language: {$lang}\n";
    echo "Text: {$text}\n";

    // Test font loading using reflection
    try {
        $reflection = new ReflectionClass($renderer);
        $method = $reflection->getMethod('getFontForLanguage');
        $method->setAccessible(true);
        $fontPath = $method->invoke($renderer, $lang);
        echo "Font Path: " . ($fontPath ?: 'NOT FOUND') . "\n";
        echo "Font Exists: " . (file_exists($fontPath) ? 'YES' : 'NO') . "\n";

        if ($fontPath) {
            echo "Font Size: " . filesize($fontPath) . " bytes\n";
            echo "Font Name: " . basename($fontPath) . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo str_repeat("-", 50) . "\n\n";
}

echo "=== Font Directory Contents ===\n";
$fontDir = storage_path('app/public/fonts');
if (is_dir($fontDir)) {
    $files = scandir($fontDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(ttf|ttc)$/i', $file)) {
            $size = filesize($fontDir . '/' . $file);
            echo "{$file}: {$size} bytes\n";
        }
    }
} else {
    echo "Font directory not found: {$fontDir}\n";
}

echo "\n=== CJK Font Test Complete ===\n";
