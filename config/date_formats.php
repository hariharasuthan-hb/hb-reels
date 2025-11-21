<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Date Format Configuration
    |--------------------------------------------------------------------------
    |
    | Define standard date formats used throughout the application.
    | These formats ensure consistency across all pages.
    |
    */

    // Standard date formats
    'display' => 'M d, Y',           // Jan 15, 2024 - For general display
    'display_full' => 'F d, Y',     // January 15, 2024 - Full month name
    'display_short' => 'M d, Y',    // Jan 15, 2024 - Short month
    'display_time' => 'M d, Y h:i A', // Jan 15, 2024 02:30 PM - With time
    'display_datetime' => 'M d, Y · h:i A', // Jan 15, 2024 · 02:30 PM - With separator
    
    // Form input formats
    'input' => 'Y-m-d',             // 2024-01-15 - For date inputs
    'input_datetime' => 'Y-m-d H:i:s', // 2024-01-15 14:30:00 - For datetime inputs
    
    // Time only formats
    'time' => 'h:i A',              // 02:30 PM - 12 hour format
    'time_24' => 'H:i',             // 14:30 - 24 hour format
    
    // Table/list formats
    'table_date' => 'M d, Y',       // Jan 15, 2024 - For tables
    'table_datetime' => 'M d, Y h:i A', // Jan 15, 2024 02:30 PM - For tables with time
    
    // Relative formats (for recent dates)
    'relative' => true,             // Show "2 days ago" for recent dates
    'relative_threshold' => 7,      // Days threshold for relative format
];

