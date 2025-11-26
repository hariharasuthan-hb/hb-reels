<?php

require_once __DIR__ . '/vendor/autoload.php';

// Test Unicode escape sequences for Tamil text
echo "=== Unicode Escape Sequences Test ===\n\n";

// Sample Tamil text
$tamilText = "மெய் யாலுது நன்றாக தெரிய வேண்டும்";
echo "Original Tamil Text: {$tamilText}\n";

// Create a VideoRenderer instance to test the methods
$renderer = new \HbReels\EventReelGenerator\Services\VideoRenderer();

// Test Unicode escape conversion
$reflection = new ReflectionClass($renderer);
$convertMethod = $reflection->getMethod('convertToUnicodeEscapes');
$convertMethod->setAccessible(true);

$escaped = $convertMethod->invoke($renderer, $tamilText);
echo "Unicode Escapes: {$escaped}\n";

// Test complex script detection
$isComplexMethod = $reflection->getMethod('isComplexScript');
$isComplexMethod->setAccessible(true);

$isComplex = $isComplexMethod->invoke($renderer, $tamilText);
echo "Is Complex Script: " . ($isComplex ? 'YES' : 'NO') . "\n";

// Test ASS rendering with Unicode escapes
$renderMethod = $reflection->getMethod('renderTextWithUnicodeEscapes');
$renderMethod->setAccessible(true);

$assRendered = $renderMethod->invoke($renderer, $tamilText);
echo "ASS Rendered: {$assRendered}\n";

echo "\n=== Configuration Options ===\n";
echo "To enable Unicode escapes globally, add to .env:\n";
echo "EVENTREEL_USE_UNICODE_ESCAPES=true\n";

echo "\nThis will use Unicode escape sequences like \\u0BA4\\u0BC6\\u0BAF\\u0BCD instead of direct Tamil characters.\n";
echo "This can help with ASS subtitle compatibility issues.\n";
