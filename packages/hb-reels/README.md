# HB Reels - Intelligent Reel Generator

A Laravel package for automatically generating 5-second vertical video reels from flyer images or text using **context-aware AI**, OCR, and stock footage. Perfect for events, announcements, acknowledgements, promotions, and any message!

## Features

- 📸 Upload flyer images (PNG/JPG) or paste any text description
- 🤖 **Context-aware AI** powered extraction using local Ollama (understands events, announcements, acknowledgements, etc.)
- 🔍 OCR text extraction from images using Tesseract
- 🎬 Automatic stock video fetching from Pexels
- 🎥 FFmpeg-based video rendering with text overlays
- 📱 Vertical format (1080x1920) optimized for social media
- 📝 Auto word-wrapping for long text
- 🎨 Professional text styling with shadows and borders for readability
- 🧠 **Smart content detection** - AI automatically identifies content type and extracts relevant information
- ⚙️ Fully configurable and publishable assets

## 🎉 What's New - Context-Aware AI!

**Major Update:** The AI now understands ANY type of content, not just events!

### Before:
- ❌ Only worked with event descriptions
- ❌ Required specific fields (event name, date, location, etc.)
- ❌ Rigid format requirements

### Now:
- ✅ Works with events, announcements, acknowledgements, promotions, any message!
- ✅ AI automatically identifies content type
- ✅ Extracts relevant information based on context
- ✅ Just write naturally - AI figures it out!

**Examples:**
- Write "Schools closed Monday due to rain" → AI creates perfect announcement reel
- Write "Congrats Team Phoenix on winning award!" → AI creates recognition reel  
- Write "Join our party Friday 8 PM" → AI creates event reel
- Write "New iPhone launching Dec 1, pre-order now!" → AI creates promo reel

No format required - just tell it what you want to say! 🚀

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

#### Ollama (Optional, for AI - Highly Recommended!)

Ollama enables **context-aware AI** that understands different content types automatically.

**macOS/Linux:**
```bash
curl -fsSL https://ollama.ai/install.sh | sh
ollama pull mistral
ollama serve  # Start the server
```

**Windows:**
Download from https://ollama.ai/download

**Verify Installation:**
```bash
ollama --version
curl http://localhost:11434/api/version
```

**Note:** Without Ollama, the package falls back to regex-based extraction, but you'll miss the intelligent context-aware features!

## Usage

### Web Interface

Visit `/event-reel` (or your configured route prefix) to access the generator interface.

#### Option 1: Text Description Only
1. Enter your message in natural language - can be an **event**, **announcement**, **acknowledgement**, or **any content**
2. Click "Generate"
3. **Result**: Stock video with AI-extracted captions overlaid (clean text with shadow/outline, no background box)

**Examples:**
- **Event**: "Join us for Summer Sunset Party on Friday, Nov 21 at 7:00 PM at Rooftop Bar, Downtown. Enjoy live DJ sets and open bar!"
- **Announcement**: "All schools in Karur district will remain closed on Monday, Nov 25 due to heavy rainfall. Stay safe!"
- **Acknowledgement**: "Congratulations to Team Phoenix for winning the Innovation Award 2025! Your hard work inspires us all."
- **Promotion**: "New iPhone 16 Pro launching December 1st! Pre-order now and get 20% off. Limited time offer!"

#### Option 2: Flyer Image + Text Overlay (Invitation Style)
1. Upload a flyer image
2. **DO NOT check** "Add background behind flyer"
3. Click "Generate"
4. **Result**: Flyer image centered on stock video background + AI-extracted details overlaid on the flyer (creates invitation card effect)

#### Option 3: Flyer Image Only (Clean Display)
1. Upload a flyer image
2. **CHECK** "Add background behind flyer"
3. Click "Generate"
4. **Result**: Flyer image centered on stock video background with NO text overlay (clean flyer showcase)

### What AI Extracts Automatically (Context-Aware)

The AI intelligently analyzes your text and automatically identifies the content type, then extracts the most relevant information:

**For Events:**
- Event name/title
- Date & time
- Location/venue
- Key highlights/activities
- Call to action (RSVP, register, etc.)

**For Announcements:**
- Main announcement message
- Important details
- Date/time (if applicable)
- Action items or instructions

**For Acknowledgements:**
- Who is being acknowledged
- Achievement or reason
- Appreciation message
- Recognition details

**For General Content:**
- Main message/title
- Key points (3-5 most important details)
- Call to action (if any)

The AI automatically formats everything into **3-5 short, punchy lines** suitable for video overlays. The text appears as clean, multi-line captions with professional styling (white text, black outline, drop shadow for readability on any background).

### Programmatic Usage

```php
use HbReels\EventReelGenerator\Services\OCRService;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\PexelsService;
use HbReels\EventReelGenerator\Services\VideoRenderer;

// Extract text from image (optional, if using flyer)
$ocr = app(OCRService::class);
$text = $ocr->extractText('/path/to/flyer.jpg');

// Or use direct text input (events, announcements, any content)
$text = "Join us for Summer Sunset Party on Friday, Nov 21 at 7:00 PM at Rooftop Bar...";

// Extract structured details using context-aware AI
$ai = app(AIService::class);
$contentDetails = $ai->extractEventDetails($text);
// Returns: ['line1' => '...', 'line2' => '...', 'line3' => '...', 'line4' => '...', 'line5' => '...']
// AI automatically identifies content type and extracts relevant information

// Generate search caption for stock video
$caption = $ai->generateCaption($text);

// Get stock video
$pexels = app(PexelsService::class);
$videoPath = $pexels->downloadVideo($caption);

// Format overlay text (filter out empty lines)
$lines = [];
for ($i = 1; $i <= 5; $i++) {
    if (!empty($contentDetails["line{$i}"])) {
        $lines[] = $contentDetails["line{$i}"];
    }
}
$overlayText = implode("\n", $lines);

// Render final video with multi-line text overlay
$renderer = app(VideoRenderer::class);
$outputPath = $renderer->render(
    stockVideoPath: $videoPath,
    flyerPath: null, // or '/path/to/flyer.jpg' to show flyer
    caption: $overlayText
);
```

**Example Output for Different Content Types:**

```php
// Event
$text = "Tech Meetup on Dec 5 at 6 PM at Innovation Hub. Network with leaders!";
$details = $ai->extractEventDetails($text);
// Result: ['line1' => 'Tech Meetup', 'line2' => 'December 5 at 6 PM', 'line3' => 'Innovation Hub', 'line4' => 'Network with industry leaders', 'line5' => 'Join us!']

// Announcement
$text = "All Karur schools closed Monday due to heavy rainfall. Stay safe!";
$details = $ai->extractEventDetails($text);
// Result: ['line1' => 'School Closure Alert', 'line2' => 'Karur District Schools', 'line3' => 'Closed Monday', 'line4' => 'Heavy Rainfall - Stay Safe']

// Acknowledgement
$text = "Congratulations Team Phoenix! Innovation Award 2025 Winner. Your dedication inspires us!";
$details = $ai->extractEventDetails($text);
// Result: ['line1' => 'Congratulations Team Phoenix!', 'line2' => 'Innovation Award 2025 Winner', 'line3' => 'Your dedication inspires us']
```

## Context-Aware AI Features 🧠

### What Makes It Smart?

The AI service now **intelligently understands** the context of your text and adapts accordingly:

#### 🎯 Automatic Content Type Detection
The AI analyzes your input and automatically identifies whether it's:
- **Event** → Extracts title, date/time, location, highlights, CTA
- **Announcement** → Extracts main message, details, important dates, actions
- **Acknowledgement** → Extracts who, what, why, appreciation
- **Promotion** → Extracts product/offer, benefits, dates, CTA
- **General Message** → Extracts key points in logical order

#### 📝 Smart Extraction
- **No rigid format required** - Just write naturally!
- **Adaptive field extraction** - Gets 3-5 most relevant details based on content
- **Intelligent line breaks** - Automatically formats for video readability
- **Missing info handling** - Skips empty fields gracefully (no "TBA" placeholders)

#### 🎨 Use Cases

**Perfect for:**
- 🎉 Event promotions (parties, conferences, meetups)
- 📢 Public announcements (closures, updates, alerts)
- 🏆 Recognition & awards (employee of month, achievements)
- 🎁 Product launches & promotions (sales, new releases)
- 📣 General messaging (tips, quotes, information)
- 🎓 Educational content (course announcements, deadlines)
- 💼 Business updates (policy changes, news)

#### 🔄 Migration from Old Format

**Old (event-specific):**
```php
['event_name' => '...', 'date_time' => '...', 'location' => '...', 'highlights' => '...', 'call_to_action' => '...']
```

**New (flexible, context-aware):**
```php
['line1' => '...', 'line2' => '...', 'line3' => '...', 'line4' => '...', 'line5' => '...']
```

The new format works for **any content type** and the AI decides what information is most important!

## Automation

### Command Line Usage

Generate reels from the CLI (useful for cron jobs or automation pipelines). Works with **any content type** - events, announcements, acknowledgements, promotions, etc.

```bash
# Event
php artisan eventreel:generate \
    --text="Join us for Tech Innovators Meetup on Dec 3, 6 PM at Startup Hub. Connect with founders!" \
    --output=storage/app/eventreel/output/tech-meetup.mp4

# Announcement
php artisan eventreel:generate \
    --text="All schools in Karur district closed Monday, Nov 25 due to heavy rainfall. Stay safe!" \
    --output=storage/app/eventreel/output/school-closure.mp4

# Acknowledgement
php artisan eventreel:generate \
    --text="Congratulations Team Phoenix for winning Innovation Award 2025! Your dedication inspires us." \
    --output=storage/app/eventreel/output/team-award.mp4
```

**Options:**
- `--flyer=/absolute/path/to/flyer.jpg` – Extract text from image via OCR
- `--text="Your message"` – Provide any message directly (event, announcement, etc.)
- `--show-flyer` – Overlay the flyer image on the video (requires `--flyer`)
- `--output=/path/to/output.mp4` – Custom output path (optional)

The AI will automatically understand the context and extract relevant information!

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
- Check if Ollama is listening: `lsof -i :11434` or `curl http://localhost:11434/api/version`
- Check `OLLAMA_URL` in `.env` (default: `http://localhost:11434`)
- Package will fallback to intelligent regex-based text extraction if Ollama is unavailable
- For best results, ensure Mistral model is downloaded: `ollama pull mistral`
- **Context-aware AI**: The AI intelligently handles events, announcements, acknowledgements, and any content type automatically!

### Ollama memory issues
If you see "failed to allocate context" or memory errors:
- Restart Ollama: `pkill ollama && ollama serve`
- Use a smaller model: `ollama pull phi` (1.6GB instead of 4.4GB mistral)
- Check available system memory
- Close other resource-intensive applications

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

