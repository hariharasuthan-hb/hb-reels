<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the path to FFmpeg executable. If FFmpeg is in your system
    | PATH, you can leave this as null. Otherwise, provide the full path
    | to the FFmpeg executable.
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Video Conversion Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for video conversion to web-compatible format.
    |
    */

    'conversion' => [
        // Quality: low, medium, high
        'quality' => env('VIDEO_QUALITY', 'medium'),
        
        // Maximum resolution (width x height)
        'max_resolution' => env('VIDEO_MAX_RESOLUTION', '1920x1080'),
        
        // Video bitrate (e.g., '2M' for 2 Mbps)
        'bitrate' => env('VIDEO_BITRATE', null),
        
        // Audio bitrate (e.g., '128k' for 128 kbps)
        'audio_bitrate' => env('VIDEO_AUDIO_BITRATE', '128k'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Formats
    |--------------------------------------------------------------------------
    |
    | List of video formats that can be uploaded and converted.
    |
    */

    'allowed_formats' => [
        'mp4', 'webm', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'ogv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Format
    |--------------------------------------------------------------------------
    |
    | The format to convert all videos to (web-compatible).
    |
    */

    'output_format' => 'mp4',
];

