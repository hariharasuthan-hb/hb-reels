<?php

/**
 * Test script to generate video for Summer Beats Music Festival
 * Run: php test_video_generation.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use HbReels\EventReelGenerator\Jobs\GenerateVideoReel;
use App\Models\User;

// Event details
$eventText = "Summer Beats Music Festival

Date: July 22, 2025
Time: 6:00 PM onwards
Venue: Skyline Arena, Downtown
Ticket Price: ₹799 per person
Includes: Live bands, EDM DJ, Food Stalls, Light Show
Tone: Energetic, youthful, exciting";

// Get first admin or member user (for testing)
$user = User::whereHas('roles', function($q) {
    $q->whereIn('name', ['admin', 'member']);
})->first();

if (!$user) {
    echo "Error: No admin or member user found. Please create a user first.\n";
    exit(1);
}

echo "Generating video for: Summer Beats Music Festival\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Event Text:\n{$eventText}\n\n";

// Prepare job data
$jobData = [
    'event_text' => $eventText,
    'show_flyer' => false, // Set to true if you want to show a flyer
];

// Dispatch the job
try {
    GenerateVideoReel::dispatch($jobData, $user->id, null);
    echo "✅ Video generation job queued successfully!\n";
    echo "The video will be generated in the background.\n";
    echo "Check the queue worker output or activity logs to see progress.\n";
    echo "\nTo process the queue, run: php artisan queue:work\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

