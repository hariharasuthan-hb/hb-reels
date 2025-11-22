# HB Reels - Event Reel Generator

A Laravel package for automatically generating 5-second vertical event reels from flyer images or text using AI, OCR, and stock footage.

## Features

- 📸 Upload flyer images (PNG/JPG) or paste event text
- 🤖 AI-powered event detail extraction using local Ollama
- 🔍 OCR text extraction from images using Tesseract
- 🎬 Automatic stock video fetching from Pexels
- 🎥 FFmpeg-based video rendering with text overlays
- 📱 Vertical format (1080x1920) optimized for social media
- 📝 Auto word-wrapping for long text
- 🎨 Professional text styling with shadows and borders for readability
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
PEXELS_API_KEY=your_pexels_api_key_here
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=mistral
TESSERACT_PATH=tesseract
FFMPEG_PATH=ffmpeg
EVENTREEL_ROUTE_PREFIX=event-reel
EVENTREEL_STORAGE_DISK=local
EVENTREEL_FONT_PATH=  # Optional: path to custom TTF font
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

#### Option 1: Text Description Only
1. Enter event description in natural language (e.g., "Join us for Summer Sunset Party on Friday, Nov 21 at 7:00 PM at Rooftop Bar, Downtown. Enjoy live DJ sets, art installations, and open bar. RSVP now!")
2. Click "Generate"
3. **Result**: Stock video with AI-extracted captions overlaid (clean text with shadow/outline, no background box)

#### Option 2: Flyer Image + Text Overlay (Invitation Style)
1. Upload a flyer image
2. **DO NOT check** "Add background behind flyer"
3. Click "Generate"
4. **Result**: Flyer image centered on stock video background + AI-extracted event details overlaid on the flyer (creates invitation card effect)

#### Option 3: Flyer Image Only (Clean Display)
1. Upload a flyer image
2. **CHECK** "Add background behind flyer"
3. Click "Generate"
4. **Result**: Flyer image centered on stock video background with NO text overlay (clean flyer showcase)

### What AI Extracts Automatically

From your event description or flyer text, AI will extract:
- **Event name** - Short, catchy title
- **Date & time** - When it happens
- **Location** - Where it takes place
- **Highlights** - Key features/activities (concise)
- **Call to action** - RSVP/booking message

The text appears as clean, multi-line captions with professional styling (white text, black outline, drop shadow for readability on any background).

### Programmatic Usage

```php
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;

// Extract text from image
$ocr = app(OCRService::class);
$text = $ocr->extractText('/path/to/flyer.jpg');

// Extract structured event details using AI
$ai = app(AIService::class);
$eventDetails = $ai->extractEventDetails($text);
// Returns: ['event_name' => '...', 'date_time' => '...', 'location' => '...', etc.]

// Generate search caption for stock video
$caption = $ai->generateCaption($text);

// Get stock video
$pexels = app(PexelsService::class);
$videoPath = $pexels->downloadVideo($caption);

// Format overlay text
$overlayText = implode("\n", array_filter([
    $eventDetails['event_name'],
    $eventDetails['date_time'],
    $eventDetails['location'],
    $eventDetails['highlights'],
    $eventDetails['call_to_action'],
]));

// Render final video with multi-line text overlay
$renderer = app(VideoRenderer::class);
$outputPath = $renderer->render(
    stockVideoPath: $videoPath,
    flyerPath: null, // or '/path/to/flyer.jpg' to show flyer
    caption: $overlayText
);
```

## Automation

### Command Line Usage

Generate reels from the CLI (useful for cron jobs or automation pipelines):

```bash
php artisan eventreel:generate \
    --text="Join us for Tech Innovators Meetup on Dec 3, 6 PM at Startup Hub. Connect with founders, CTOs, and product builders!" \
    --output=storage/app/eventreel/output/tech-meetup.mp4
```

**Options:**
- `--flyer=/absolute/path/to/flyer.jpg` – Extract text from image via OCR
- `--text="Your event description"` – Provide event description directly
- `--show-flyer` – Overlay the flyer image on the video (requires `--flyer`)
- `--output=/path/to/output.mp4` – Custom output path (optional)

### NPM Scripts

Start development environment with all services:

```bash
# Start Ollama + Laravel server
npm run serve:all

# Full development with Vite hot reload + Ollama + Laravel
npm run dev:all

# Start only Ollama
npm run ollama
```

The package includes `concurrently` configuration to run multiple services simultaneously with colored output.

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
        'duration' => 5,  // seconds
        'fps' => 30,
        'font_path' => env('EVENTREEL_FONT_PATH', null), // Custom TTF font
    ],
    
    'storage' => [
        'disk' => 'local',
        'temp_path' => 'eventreel/temp',
        'output_path' => 'eventreel/output',
    ],
    
    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
    ],
    
    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
    ],
];
```

### Text Overlay Features

The package automatically:
- **Wraps long text** at ~35 characters per line for readability
- **Centers text** vertically on the video (or over flyer if flyer exists)
- **Adds professional styling**: 
  - White text with 3px black outline
  - Drop shadow (2px offset, 80% opacity) for depth
  - NO background box - clean modern look
- **Splits multi-line content** with proper spacing (80px between lines)
- **Auto-detects system fonts**: 
  - macOS: Arial Bold (`/System/Library/Fonts/Supplemental/Arial Bold.ttf`)
  - Linux: DejaVu Sans Bold (`/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf`)
  - Windows: Arial Bold (`C:\Windows\Fonts\arialbd.ttf`)
  - Custom: Set via `EVENTREEL_FONT_PATH` in `.env`

### Flyer + Caption Modes

| Checkbox State | Flyer Uploaded | Result |
|---------------|----------------|--------|
| ❌ Unchecked | No | Stock video + captions |
| ❌ Unchecked | Yes | **Flyer + captions overlay (invitation style)** |
| ✅ Checked | Yes | Flyer only (no captions) |
| ✅ Checked | No | Stock video + captions |

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
- Or set `FFMPEG_PATH` in `.env` to full path (e.g., `/usr/local/bin/ffmpeg`)

### Tesseract not found
- Ensure Tesseract is installed
- Or set `TESSERACT_PATH` in `.env` to full path

### Ollama connection failed
- Ensure Ollama is running: `ollama serve` or `npm run ollama`
- Check `OLLAMA_URL` in `.env` (default: `http://localhost:11434`)
- Package will fallback to regex-based text extraction if Ollama is unavailable
- For best results, ensure Mistral model is downloaded: `ollama pull mistral`

### Pexels API errors
- Verify your API key is correct in `.env`
- Check API rate limits (free tier: 200 requests/hour)
- Sign up at https://www.pexels.com/api/ if you don't have a key

### Text not appearing or overlapping
- Ensure you're passing actual newline characters (`\n`) not literal backslash-n (`\\n`)
- Check logs in `storage/logs/laravel.log` for text positioning debug info
- Verify font file exists if using custom font path

### Captions not showing on flyer
- **DO NOT check** the "Add background behind flyer" checkbox
- Checkbox checked = flyer only (no text)
- Checkbox unchecked = flyer + captions overlay

### Video rendering fails
- Check FFmpeg version: `ffmpeg -version` (requires 4.0+)
- Ensure sufficient disk space in `storage/app/eventreel/`
- Check write permissions on storage directories
- Review `storage/logs/laravel.log` for detailed error messages

## License

MIT License - feel free to use in your projects!

## Support

For issues and contributions, please visit: https://github.com/hb-reels/event-reel-generator

