<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CmsContent;

// Create testimonials section
CmsContent::create([
    'title' => 'Testimonials',
    'key' => 'testimonials-section',
    'type' => 'testimonials',
    'content' => 'Hear what our satisfied members have to say about their experience with HB Reels.',
    'order' => 1,
    'is_active' => true
]);

// Create individual testimonials
CmsContent::create([
    'title' => 'Sarah Johnson',
    'key' => 'testimonial-1',
    'type' => 'testimonials',
    'content' => 'HB Reels made creating event videos so easy! The AI-powered captions and stock video selection saved me hours of work. Highly recommend for event organizers.',
    'extra_data' => json_encode(['position' => 'Event Coordinator', 'rating' => 5]),
    'order' => 2,
    'is_active' => true
]);

CmsContent::create([
    'title' => 'Mike Chen',
    'key' => 'testimonial-2',
    'type' => 'testimonials',
    'content' => 'As a gym owner, I love how HB Reels helps me create professional event videos quickly. The subscription model gives me unlimited access to all features.',
    'extra_data' => json_encode(['position' => 'Gym Owner', 'rating' => 5]),
    'order' => 3,
    'is_active' => true
]);

CmsContent::create([
    'title' => 'Priya Patel',
    'key' => 'testimonial-3',
    'type' => 'testimonials',
    'content' => 'The video quality is amazing and the AI understands different languages perfectly. Perfect for our multicultural events!',
    'extra_data' => json_encode(['position' => 'Community Manager', 'rating' => 5]),
    'order' => 4,
    'is_active' => true
]);

echo "Testimonials created successfully!\n";
echo "The testimonials section should now be visible on the home page.\n";
