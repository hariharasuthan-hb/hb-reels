<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\GrammarService;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AI Grammar Checking Test for Multiple Languages ===\n\n";

$grammarService = new GrammarService();

$testCases = [
    // English tests
    ['text' => 'this is a test sentence it needs punctuation', 'language' => 'en'],
    ['text' => 'I goed to the store and buyed milk', 'language' => 'en'],

    // Spanish tests
    ['text' => 'yo voy a la tienda y compro leche', 'language' => 'es'],
    ['text' => 'ella es muy bonita y inteligente', 'language' => 'es'],

    // French tests
    ['text' => 'je vais au magasin et acheter du lait', 'language' => 'fr'],
    ['text' => 'il fait beau aujourd hui', 'language' => 'fr'],

    // German tests
    ['text' => 'ich gehe zum laden und kaufe milch', 'language' => 'de'],
    ['text' => 'das wetter ist schön heute', 'language' => 'de'],

    // Tamil tests
    ['text' => 'நான் கடைக்கு சென்று பால் வாங்கினேன்', 'language' => 'ta'],
    ['text' => 'இன்று வானிலை அழகாக இருக்கிறது', 'language' => 'ta'],

    // Hindi tests
    ['text' => 'मैं दुकान गया और दूध खरीदा', 'language' => 'hi'],
    ['text' => 'आज मौसम अच्छा है', 'language' => 'hi'],

    // Telugu tests
    ['text' => 'నేను అంగడికి వెళ్లి పాలు కొన్నాను', 'language' => 'te'],
    ['text' => 'నేటి వాతావరణం బాగుంది', 'language' => 'te'],

    // Arabic tests
    ['text' => 'ذهبت إلى المتجر واشتريت الحليب', 'language' => 'ar'],
    ['text' => 'الطقس جميل اليوم', 'language' => 'ar'],

    // Chinese tests
    ['text' => '我去了商店买了牛奶', 'language' => 'zh'],
    ['text' => '今天的天气很好', 'language' => 'zh'],
];

echo "Supported Languages: " . count($grammarService->getSupportedLanguages()) . "\n";
echo "Current Provider: " . config('eventreel.grammar_provider', 'auto') . "\n";
echo "Grammar Checking Enabled: " . (config('eventreel.grammar_enabled', false) ? 'YES' : 'NO') . "\n\n";

if (!config('eventreel.grammar_enabled', false)) {
    echo "⚠️  Grammar checking is DISABLED. Enable it in your .env file:\n";
    echo "EVENTREEL_GRAMMAR_ENABLED=true\n";
    echo "EVENTREEL_GRAMMAR_PROVIDER=auto\n\n";
    exit(1);
}

foreach ($testCases as $i => $testCase) {
    $testNumber = $i + 1;
    $languageName = $grammarService->getLanguageName($testCase['language']);

    echo "Test {$testNumber}: {$languageName} ({$testCase['language']})\n";
    echo "Input:  \"{$testCase['text']}\"\n";

    $startTime = microtime(true);
    $corrected = $grammarService->checkGrammar($testCase['text'], $testCase['language']);
    $endTime = microtime(true);

    $processingTime = round(($endTime - $startTime) * 1000, 2);

    echo "Output: \"{$corrected}\"\n";
    echo "Time: {$processingTime}ms\n";
    echo "Changed: " . ($testCase['text'] !== $corrected ? 'YES' : 'NO') . "\n";

    if ($testCase['text'] !== $corrected) {
        echo "Diff: -" . $testCase['text'] . "\n";
        echo "      +" . $corrected . "\n";
    }

    echo str_repeat("-", 60) . "\n\n";
}

echo "=== Grammar Checking Test Complete ===\n";
echo "Note: Results depend on available AI providers (Ollama/Google)\n";
