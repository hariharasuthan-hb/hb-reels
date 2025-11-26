# Event Reel Generator Tests

This directory contains comprehensive test cases for the multi-language event reel generator functionality.

## Test Structure

### AIServiceTest.php
Tests for AI-powered caption generation and translation services:
- AI caption generation from text
- Event detail extraction
- Google Translate integration
- Language detection and preprocessing

### VideoRendererTest.php
Tests for video rendering with ASS subtitles:
- FFmpeg command generation
- ASS subtitle file creation
- Font selection for different languages
- Multi-language text rendering
- Complex script support (Tamil, Arabic, CJK)

### ReelControllerTest.php
Tests for the complete workflow controller:
- Request validation
- Service integration
- Multi-language processing
- Error handling

### MultiLanguageWorkflowTest.php
End-to-end workflow tests:
- Complete English → Tamil translation pipeline
- Language detection accuracy
- Font selection verification
- ASS subtitle rendering validation

## Running Tests

### Prerequisites
1. Install dependencies:
```bash
cd packages/hb-reels
composer install
```

2. Install Orchestra Testbench (Laravel testing framework):
```bash
composer require orchestra/testbench --dev
```

### Run All Tests
```bash
# From the main Laravel project directory
./vendor/bin/phpunit packages/hb-reels/tests/

# Or run specific test files
./vendor/bin/phpunit packages/hb-reels/tests/AIServiceTest.php
./vendor/bin/phpunit packages/hb-reels/tests/VideoRendererTest.php
./vendor/bin/phpunit packages/hb-reels/tests/ReelControllerTest.php
./vendor/bin/phpunit packages/hb-reels/tests/MultiLanguageWorkflowTest.php
```

### Run with Coverage
```bash
./vendor/bin/phpunit packages/hb-reels/tests/ --coverage-html=coverage
```

## Test Categories

### Unit Tests
- Individual method testing
- Mock external dependencies (AI, Google Translate)
- Isolated functionality verification

### Integration Tests
- Service interaction testing
- End-to-end workflow validation
- Font and ASS subtitle generation

### Language Tests
- Unicode script detection
- Font selection accuracy
- Translation pipeline verification

## Test Data

The tests use mock data and don't require:
- Ollama server running
- Google Translate API access
- FFmpeg installation
- Font files (paths are mocked)

## Font Files Required for Full Testing

For complete testing, ensure these font files exist:
```
packages/hb-reels/resources/fonts/
├── noto-sans-indic/
│   ├── NotoSansTamil-Regular.ttf
│   ├── NotoSansDevanagari-Regular.ttf
│   ├── NotoSansTelugu-Regular.ttf
│   └── ...
├── noto-sans-arabic/
│   └── NotoSansArabic-Regular.ttf
├── noto-sans-cjk/
│   └── NotoSansCJK-Regular.ttc
└── noto-sans-thai/
    └── NotoSansThai-Regular.ttf
```

## Mocking Strategy

### AI Service Mocking
- HTTP client responses are mocked
- Ollama API calls return predefined JSON
- Translation calls return mock translated text

### Video Renderer Mocking
- FFmpeg commands are validated without execution
- Font file existence is mocked
- ASS subtitle generation is tested structurally

### Controller Testing
- Laravel Request objects are created
- Service dependencies are mocked
- Response validation without actual file generation

## Continuous Integration

These tests are designed to run in CI/CD pipelines and don't require:
- Network access (all external calls mocked)
- File system writes (temp files mocked)
- External services (Ollama, FFmpeg mocked)

## Adding New Tests

When adding new functionality:

1. Create test methods following naming convention: `test_feature_description`
2. Mock external dependencies
3. Test both success and failure scenarios
4. Include edge cases and error conditions
5. Update this README with new test descriptions

## Test Maintenance

- Run tests regularly to catch regressions
- Update mocks when API responses change
- Add tests for new language support
- Maintain font file compatibility tests
