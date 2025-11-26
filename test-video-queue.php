<?php

require_once __DIR__ . '/vendor/autoload.php';

// Test the queued video download system
echo "=== HB Reels Video Download Queue Test ===\n\n";

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use HbReels\EventReelGenerator\Services\PexelsService;

$pexelsService = new PexelsService();

// Test caption for video download
$testCaption = "Happy birthday celebration with cake and balloons";
$context = [
    'language' => 'en',
    'user_id' => 1,
    'request_id' => 'test_' . time()
];

echo "Test Caption: \"{$testCaption}\"\n";
echo "Context: " . json_encode($context) . "\n\n";

// Dispatch video download job to queue
echo "📤 Dispatching video download job to queue...\n";
$jobId = $pexelsService->downloadVideoQueued($testCaption, $context);

echo "✅ Job dispatched successfully!\n";
echo "📋 Job ID: {$jobId}\n\n";

// Check job status immediately (should be queued or processing)
echo "🔍 Checking job status...\n";
$status = $pexelsService->getDownloadStatus($jobId);

if ($status) {
    echo "📊 Current Status: " . ($status['status'] ?? 'unknown') . "\n";

    if (isset($status['video_path'])) {
        echo "🎬 Video Path: {$status['video_path']}\n";
    }

    if (isset($status['error'])) {
        echo "❌ Error: {$status['error']}\n";
    }
} else {
    echo "❓ Job status not available yet\n";
}

echo "\n=== Instructions ===\n";
echo "1. Run the queue processor: php artisan hb-reels:process-video-downloads\n";
echo "2. Or use npm: npm run video-queue\n";
echo "3. Check status again after processing completes\n";
echo "4. Video will be available at the returned path\n\n";

echo "=== Alternative Usage ===\n";
echo "For synchronous download (old method):\n";
echo "\$videoPath = \$pexelsService->downloadVideo('your caption');\n\n";

echo "For queued download (new method):\n";
echo "\$jobId = \$pexelsService->downloadVideoQueued('your caption');\n";
echo "\$status = \$pexelsService->getDownloadStatus(\$jobId);\n\n";
