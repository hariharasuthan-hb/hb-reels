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
    'pexels' => [
        'timeout' => env('PEXELS_TIMEOUT', 45), // API call timeout in seconds
        'connect_timeout' => env('PEXELS_CONNECT_TIMEOUT', 10), // Connection timeout in seconds
        'max_retries' => env('PEXELS_MAX_RETRIES', 3), // Number of retry attempts
        'download_timeout' => env('PEXELS_DOWNLOAD_TIMEOUT', 60), // Video download timeout in seconds
    ],

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
    | Access Code (Optional)
    |--------------------------------------------------------------------------
    |
    | If set, users must provide this access code to generate reels.
    | Leave empty to disable access code requirement.
    |
    */
    'access_code' => env('EVENTREEL_ACCESS_CODE', null),
];

