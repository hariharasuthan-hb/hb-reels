# HB Reels - Event Reel Generator

A Laravel package for automatically generating 5-second vertical event reels from flyer images or text using AI, OCR, and stock footage.

## Features

- 📸 Upload flyer images (PNG/JPG) or paste event text
- 🤖 AI-powered caption generation using local Ollama
- 🔍 OCR text extraction from images using Tesseract
- 🎬 Automatic stock video fetching from Pexels
- 🎥 FFmpeg-based video rendering
- 📱 Vertical format (1080x1920) optimized for social media
- ⚙️ Fully configurable and publishable assets

## Requirements

Before installing, ensure you have:

- **PHP 8.1+**
- **Laravel 10+ or 11+**
- **FFmpeg** installed and accessible in PATH
- **Tesseract OCR** installed and accessible in PATH
- **Ollama** running locally (optional, for AI caption generation)
- **Pexels API Key** (free at https://www.pexels.com/api/)

## Installation

### Step 1: Install via Composer

```bash
composer require hb-reels/event-reel-generator
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=eventreel-config
```

### Step 3: Publish Views (Optional)

```bash
php artisan vendor:publish --tag=eventreel-views
```

### Step 4: Configure Environment Variables

Add to your `.env` file:

```env
PEXELS_API_KEY=OFJfQjJ4yJzccoSokaSDFByWoHaTDXaisZkuF8v9aas6ISXabomPsfiM
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=mistral
TESSERACT_PATH=tesseract
FFMPEG_PATH=ffmpeg
EVENTREEL_ROUTE_PREFIX=event-reel
EVENTREEL_STORAGE_DISK=local
```

### Step 5: Install System Dependencies

#### FFmpeg

**macOS:**
```bash
brew install ffmpeg
```

**Ubuntu/Debian:**
```bash
sudo apt-get install ffmpeg
```

**Windows:**
Download from https://ffmpeg.org/download.html

#### Tesseract OCR

**macOS:**
```bash
brew install tesseract
```

**Ubuntu/Debian:**
```bash
sudo apt-get install tesseract-ocr
```

**Windows:**
Download from https://github.com/UB-Mannheim/tesseract/wiki

#### Ollama (Optional, for AI)

**macOS/Linux:**
```bash
curl -fsSL https://ollama.ai/install.sh | sh
ollama pull mistral
```

**Windows:**
Download from https://ollama.ai/download

## Usage

### Web Interface

Visit `/event-reel` (or your configured route prefix) to access the generator interface.

1. Upload a flyer image OR paste event text
2. Optionally check "Add background behind flyer"
3. Click "Generate"
4. Download your 5-second reel!

### Programmatic Usage

```php
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;

// Extract text from image
$ocr = app(OCRService::class);
$text = $ocr->extractText('/path/to/flyer.jpg');

// Generate AI caption
$ai = app(AIService::class);
$caption = $ai->generateCaption($text);

// Get stock video
$pexels = app(PexelsService::class);
$videoPath = $pexels->downloadVideo($caption);

// Render final video
$renderer = app(VideoRenderer::class);
$outputPath = $renderer->render(
    stockVideoPath: $videoPath,
    flyerPath: '/path/to/flyer.jpg',
    caption: $caption
);
```

## Automation

If you want to generate a reel from the CLI (useful for cron jobs or automation pipelines), the package ships with a command:

```
php artisan eventreel:generate \
    --text="Friendsgiving karaoke · Nov 18, 8:30 PM · Orem, UT" \
    --output=storage/app/eventreel/output/friendsgiving.mp4
```

- `--flyer=/absolute/path/to/flyer.jpg` – OCRs text from the image and uses it in the caption  
- `--text=` – Provide manual text instead of uploading a flyer  
- `--show-flyer` – Overlay the flyer on the stock video (requires `--flyer`)  
- `--output=` – Optional destination path; otherwise the generated file remains under the configured storage disk  

You can script this command inside deployment hooks or queue workers to automatically produce reels whenever new event data arrives.

## Testing

Inside the package directory (or once it is installed via Composer), run:

```
composer test
```

Or execute PHPUnit directly:

```
./vendor/bin/phpunit
```

The included `tests/VideoRendererTest.php` guards against FFmpeg command quoting regressions—add more tests there before releasing a new version.

## Configuration

### Config File: `config/eventreel.php`

```php
return [
    'route_prefix' => 'event-reel',
    'route_name_prefix' => 'eventreel.',
    
    'pexels_api_key' => env('PEXELS_API_KEY'),
    'ollama_url' => env('OLLAMA_URL', 'http://localhost:11434'),
    'ollama_model' => env('OLLAMA_MODEL', 'mistral'),
    
    'video' => [
        'width' => 1080,
        'height' => 1920,
        'duration' => 5,
        'fps' => 30,
    ],
    
    'storage' => [
        'disk' => 'local',
        'temp_path' => 'eventreel/temp',
        'output_path' => 'eventreel/output',
    ],
];
```

## Routes

The package automatically registers routes:

- `GET /event-reel` - Show generator form
- `POST /event-reel/generate` - Generate reel

Routes are prefixed with `eventreel.` by default.

## Storage

Generated videos are stored in:
- `storage/app/eventreel/output/` (default)

Temporary files are cleaned up automatically after generation.

## Troubleshooting

### FFmpeg not found
- Ensure FFmpeg is installed and in your system PATH
- Or set `FFMPEG_PATH` in `.env` to full path

### Tesseract not found
- Ensure Tesseract is installed
- Or set `TESSERACT_PATH` in `.env` to full path

### Ollama connection failed
- Ensure Ollama is running: `ollama serve`
- Check `OLLAMA_URL` in `.env`
- Package will fallback to simple text processing if Ollama is unavailable

### Pexels API errors
- Verify your API key is correct
- Check API rate limits (free tier: 200 requests/hour)

## License

MIT License - feel free to use in your projects!

## Support

For issues and contributions, please visit: https://github.com/hb-reels/event-reel-generator

