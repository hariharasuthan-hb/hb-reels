<?php

namespace HbReels\EventReelGenerator\Tests;

use HbReels\EventReelGenerator\Controllers\ReelController;
use HbReels\EventReelGenerator\Services\AIService;
use HbReels\EventReelGenerator\Services\VideoRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\TestCase;
use Mockery;

class ReelControllerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['HbReels\EventReelGenerator\EventReelServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('eventreel.ollama_url', 'http://localhost:11434');
        $app['config']->set('eventreel.ollama_model', 'mistral');
        $app['config']->set('eventreel.use_google_translate', true);
        $app['config']->set('filesystems.disks.local.root', '/tmp');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_generate_handles_english_input_tamil_output()
    {
        // Mock the services
        $aiService = Mockery::mock(AIService::class);
        $videoRenderer = Mockery::mock(VideoRenderer::class);

        // Mock AI service responses
        $aiService->shouldReceive('generateCaption')
            ->once()
            ->with('Coimbatore Food Festival Celebration', 'en')
            ->andReturn('Coimbatore Food Festival');

        $aiService->shouldReceive('translateWithGoogle')
            ->once()
            ->with('Coimbatore Food Festival', 'ta', 'en')
            ->andReturn('கோவை உணவு விழா');

        $videoRenderer->shouldReceive('render')
            ->once()
            ->withArgs(function($args) {
                return $args['caption'] === 'கோவை உணவு விழா' &&
                       $args['language'] === 'ta';
            })
            ->andReturn('/path/to/generated/video.mp4');

        // Create controller instance
        $controller = new ReelController();

        // Mock the request
        $request = Request::create('/generate', 'POST', [
            'event_text' => 'Coimbatore Food Festival Celebration',
            'language' => 'ta',
            'show_flyer' => '0'
        ]);

        // Mock the container bindings
        app()->instance(AIService::class, $aiService);
        app()->instance(VideoRenderer::class, $videoRenderer);

        // Mock Storage and other dependencies
        Storage::shouldReceive('disk->put')->andReturn(true);
        Storage::shouldReceive('disk->download')->andReturn(response('video content'));

        // Execute the method
        $response = $controller->generate($request);

        // Assert the response is successful
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    public function test_generate_handles_english_input_english_output()
    {
        // Mock the services
        $aiService = Mockery::mock(AIService::class);
        $videoRenderer = Mockery::mock(VideoRenderer::class);

        // Mock AI service responses
        $aiService->shouldReceive('generateCaption')
            ->once()
            ->with('Coimbatore Food Festival Celebration', 'en')
            ->andReturn('Coimbatore Food Festival');

        $aiService->shouldReceive('extractEventDetails')
            ->once()
            ->with('Coimbatore Food Festival Celebration', 'en')
            ->andReturn([
                'line1' => 'Coimbatore Food Festival',
                'line2' => 'Celebration Event',
                'line3' => 'Join Us!'
            ]);

        $videoRenderer->shouldReceive('render')
            ->once()
            ->withArgs(function($args) {
                return strpos($args['caption'], 'Coimbatore Food Festival') !== false &&
                       $args['language'] === 'en';
            })
            ->andReturn('/path/to/generated/video.mp4');

        // Create controller instance
        $controller = new ReelController();

        // Mock the request
        $request = Request::create('/generate', 'POST', [
            'event_text' => 'Coimbatore Food Festival Celebration',
            'language' => 'en',
            'show_flyer' => '0'
        ]);

        // Mock the container bindings
        app()->instance(AIService::class, $aiService);
        app()->instance(VideoRenderer::class, $videoRenderer);

        // Execute the method
        $response = $controller->generate($request);

        // Assert the response is successful
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    public function test_generate_handles_auto_language_detection()
    {
        // Mock the services
        $aiService = Mockery::mock(AIService::class);
        $videoRenderer = Mockery::mock(VideoRenderer::class);

        // Mock AI service responses
        $aiService->shouldReceive('generateCaption')
            ->once()
            ->with('கோவை உணவு விழா கொண்டாட்டம்', 'en')
            ->andReturn('Coimbatore Food Festival');

        $aiService->shouldReceive('extractEventDetails')
            ->once()
            ->with('கோவை உணவு விழா கொண்டாட்டம்', 'en')
            ->andReturn([
                'line1' => 'கோவை உணவு விழா',
                'line2' => 'கொண்டாட்ட நிகழ்வு',
                'line3' => 'எங்களுடன் இணையுங்கள்!'
            ]);

        $videoRenderer->shouldReceive('render')
            ->once()
            ->withArgs(function($args) {
                return strpos($args['caption'], 'கோவை உணவு விழா') !== false &&
                       $args['language'] === 'auto';
            })
            ->andReturn('/path/to/generated/video.mp4');

        // Create controller instance
        $controller = new ReelController();

        // Mock the request with Tamil input and auto language
        $request = Request::create('/generate', 'POST', [
            'event_text' => 'கோவை உணவு விழா கொண்டாட்டம்',
            'language' => 'auto',
            'show_flyer' => '0'
        ]);

        // Mock the container bindings
        app()->instance(AIService::class, $aiService);
        app()->instance(VideoRenderer::class, $videoRenderer);

        // Execute the method
        $response = $controller->generate($request);

        // Assert the response is successful
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    public function test_format_content_overlay_creates_proper_text()
    {
        $controller = new ReelController();
        $reflection = new \ReflectionClass(ReelController::class);
        $method = $reflection->getMethod('formatContentOverlay');
        $method->setAccessible(true);

        // Test with multiple lines
        $contentDetails = [
            'line1' => 'கோவை நகரில்',
            'line2' => 'உணவு விழா',
            'line3' => 'நடைபெறும்',
            'line4' => '',
            'line5' => 'வருக!'
        ];

        $result = $method->invoke($controller, $contentDetails);

        // Should contain line breaks and skip empty lines
        $expected = "கோவை நகரில்\nஉணவு விழா\nநடைபெறும்\nவருக!";
        $this->assertEquals($expected, $result);
    }

    public function test_extract_event_text_from_request()
    {
        $controller = new ReelController();
        $reflection = new \ReflectionClass(ReelController::class);
        $method = $reflection->getMethod('extractEventText');
        $method->setAccessible(true);

        // Test with direct text input
        $request = Request::create('/', 'POST', ['event_text' => 'Test event text']);
        $ocrService = Mockery::mock('HbReels\EventReelGenerator\Services\OCRService');

        $result = $method->invoke($controller, $request, $ocrService);
        $this->assertEquals('Test event text', $result);
    }

    public function test_generate_validates_request_parameters()
    {
        $controller = new ReelController();

        // Test missing required parameters
        $request = Request::create('/generate', 'POST', []);
        $response = $controller->generate($request);

        // Should redirect back with errors
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_generate_handles_image_upload()
    {
        // This would require more complex mocking of file uploads
        // For now, we'll test the basic structure is in place
        $controller = new ReelController();

        $this->assertTrue(method_exists($controller, 'generate'));
        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_generate_handles_access_code_validation()
    {
        $controller = new ReelController();

        // Mock config to require access code
        config(['eventreel.access_code' => 'secret123']);

        $request = Request::create('/generate', 'POST', [
            'event_text' => 'Test event',
            'access_code' => 'wrong123',
            'language' => 'en'
        ]);

        $response = $controller->generate($request);

        // Should redirect back with access code error
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_workflow_integration_english_to_tamil()
    {
        // Comprehensive integration test for the English → Tamil workflow

        // Mock all services
        $aiService = Mockery::mock(AIService::class);
        $videoRenderer = Mockery::mock(VideoRenderer::class);

        // Step 1: AI generates English caption for video search
        $aiService->shouldReceive('generateCaption')
            ->once()
            ->with('Coimbatore City Food Festival Event', 'en')
            ->andReturn('Coimbatore Food Festival Celebration');

        // Step 2: Direct translation to Tamil (since language is 'ta')
        $aiService->shouldReceive('translateWithGoogle')
            ->once()
            ->with('Coimbatore Food Festival Celebration', 'ta', 'en')
            ->andReturn('கோவை உணவு விழா கொண்டாட்டம்');

        // Step 3: Video renderer receives Tamil text
        $videoRenderer->shouldReceive('render')
            ->once()
            ->with(
                stockVideoPath: Mockery::any(),
                flyerPath: null,
                caption: 'கோவை உணவு விழா கொண்டாட்டம்',
                language: 'ta'
            )
            ->andReturn('/generated/video.mp4');

        // Execute the workflow
        $controller = new ReelController();
        $request = Request::create('/generate', 'POST', [
            'event_text' => 'Coimbatore City Food Festival Event',
            'language' => 'ta',
            'show_flyer' => '0'
        ]);

        // Bind mocks to container
        app()->instance(AIService::class, $aiService);
        app()->instance(VideoRenderer::class, $videoRenderer);

        // Mock storage operations
        \Illuminate\Support\Facades\Storage::shouldReceive('disk->put')->andReturn(true);
        \Illuminate\Support\Facades\Storage::shouldReceive('disk->delete')->andReturn(true);
        \Illuminate\Support\Facades\Storage::shouldReceive('disk->download')->andReturn(response('video'));

        $response = $controller->generate($request);

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }
}
