<?php

namespace Database\Seeders;

use App\Models\LandingPageContent;
use Illuminate\Database\Seeder;

class LandingPageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LandingPageContent::firstOrCreate(
            ['id' => 1],
            [
                'welcome_title' => 'Welcome to Our Gym',
                'welcome_subtitle' => 'Transform your body, transform your life',
                'about_title' => 'About Us',
                'about_description' => 'We are dedicated to helping you achieve your fitness goals with state-of-the-art equipment, expert trainers, and a supportive community.',
                'about_features' => [
                    [
                        'icon' => '💪',
                        'title' => 'Expert Trainers',
                        'description' => 'Certified professionals to guide you'
                    ],
                    [
                        'icon' => '🏋️',
                        'title' => 'Modern Equipment',
                        'description' => 'Latest fitness equipment available'
                    ],
                    [
                        'icon' => '👥',
                        'title' => 'Community Support',
                        'description' => 'Join a supportive fitness community'
                    ],
                ],
                'services_title' => 'Our Services',
                'services_description' => 'Choose from our range of fitness programs and services',
                'services' => [
                    [
                        'title' => 'Personal Training',
                        'description' => 'One-on-one training sessions with expert trainers'
                    ],
                    [
                        'title' => 'Group Classes',
                        'description' => 'Join group fitness classes for motivation'
                    ],
                    [
                        'title' => 'Nutrition Plans',
                        'description' => 'Holistic guidance for balanced habits'
                    ],
                    [
                        'title' => 'Cardio Zone',
                        'description' => 'State-of-the-art cardio equipment'
                    ],
                    [
                        'title' => 'Weight Training',
                        'description' => 'Comprehensive weight training facilities'
                    ],
                    [
                        'title' => 'Yoga & Meditation',
                        'description' => 'Relax and rejuvenate with yoga classes'
                    ],
                ],
                'is_active' => true,
            ]
        );
    }
}
