<?php

/**
 * Permanently fix "Broken pipe" notices in Laravel's server.php
 * This script patches the vendor file to suppress the harmless error
 */

$serverFile = __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';

if (!file_exists($serverFile)) {
    echo "Server.php file not found. Skipping patch.\n";
    exit(0);
}

$content = file_get_contents($serverFile);

// Check if already patched
if (strpos($content, '@file_put_contents') !== false) {
    echo "Server.php already patched. Skipping.\n";
    exit(0);
}

// Simple approach: find the line with file_put_contents to php://stdout and add @ prefix
// Match any whitespace before file_put_contents that writes to php://stdout
$patched = preg_replace(
    '/(\s+)(file_put_contents\s*\(\s*[\'"]php:\/\/stdout[\'"]\s*,)/',
    '$1@$2',
    $content
);

if ($patched !== $content) {
    file_put_contents($serverFile, $patched);
    echo "Successfully patched server.php to suppress broken pipe errors.\n";
} else {
    echo "Could not find the line to patch in server.php.\n";
    exit(1);
}

