<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route prefix and name prefix for the event reel generator.
    |
    */
    'route_prefix' => env('EVENTREEL_ROUTE_PREFIX', 'event-reel'),
    'route_name_prefix' => env('EVENTREEL_ROUTE_NAME_PREFIX', 'eventreel.'),

    /*
    |--------------------------------------------------------------------------
    | Pexels API Configuration
    |--------------------------------------------------------------------------
    |
    | Your Pexels API key for fetching stock videos.
    | Get your free API key at: https://www.pexels.com/api/
    |
    */
    'pexels_api_key' => env('PEXELS_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for local Ollama server for AI caption generation.
    | Make sure Ollama is running locally before using this feature.
    |
    */
    'ollama_url' => env('OLLAMA_URL', 'http://localhost:11434'),
    'ollama_model' => env('OLLAMA_MODEL', 'mistral'),

    /*
    |--------------------------------------------------------------------------
    | Multi-Language Support
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-language caption generation and video rendering.
    |
    */
    'use_google_translate' => env('EVENTREEL_USE_GOOGLE_TRANSLATE', true),
    'default_language' => env('EVENTREEL_DEFAULT_LANGUAGE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Grammar Checking Configuration
    |--------------------------------------------------------------------------
    |
    | AI-powered grammar and punctuation checking for multiple languages.
    | Supports Ollama (local) and Google Language API (cloud).
    |
    */
    'grammar_enabled' => env('EVENTREEL_GRAMMAR_ENABLED', false),
    'grammar_provider' => env('EVENTREEL_GRAMMAR_PROVIDER', 'auto'), // 'ollama', 'google', 'auto'
    'fallback_translation' => env('EVENTREEL_FALLBACK_TRANSLATION', true),

    /*
    |--------------------------------------------------------------------------
    | Font Configuration
    |--------------------------------------------------------------------------
    |
    | Font settings for multi-language text rendering in videos.
    | Package fonts are prioritized over system fonts.
    |
    */
    'fonts' => [
        'package_path' => __DIR__ . '/../resources/fonts/',
        'fallback_font' => 'NotoSans-Regular.ttf',
        'cache_fonts' => env('EVENTREEL_CACHE_FONTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for generated video reels.
    |
    */
    'video' => [
        'width' => 1080,
        'height' => 1920,
        'duration' => 5, // seconds
        'fps' => 30,
        'format' => 'mp4',
        'font_path' => env('EVENTREEL_FONT_PATH', null), // Path to TTF font file for captions
        'use_unicode_escapes' => env('EVENTREEL_USE_UNICODE_ESCAPES', false), // Use Unicode escape sequences for complex scripts
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Storage disk and paths for temporary and generated files.
    |
    */
    'storage' => [
        'disk' => env('EVENTREEL_STORAGE_DISK', 'local'),
        'temp_path' => env('EVENTREEL_TEMP_PATH', 'eventreel/temp'),
        'output_path' => env('EVENTREEL_OUTPUT_PATH', 'eventreel/output'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR Configuration
    |--------------------------------------------------------------------------
    |
    | Tesseract OCR configuration.
    | Make sure Tesseract is installed on your system.
    |
    */
    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
        'language' => env('TESSERACT_LANGUAGE', 'eng'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | FFmpeg binary path.
    | Make sure FFmpeg is installed on your system.
    |
    */
    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for background processing.
    |
    */
    'queue' => [
        'video_downloads' => [
            'queue_name' => env('VIDEO_DOWNLOAD_QUEUE', 'video-downloads'),
            'tries' => env('VIDEO_DOWNLOAD_TRIES', 3),
            'timeout' => env('VIDEO_DOWNLOAD_TIMEOUT', 300),
            'retry_until_minutes' => env('VIDEO_DOWNLOAD_RETRY_UNTIL', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Code (Optional)
    |--------------------------------------------------------------------------
    |
    | If set, users must provide this access code to generate reels.
    | Leave empty to disable access code requirement.
    |
    */
    'access_code' => env('EVENTREEL_ACCESS_CODE', null),
];

