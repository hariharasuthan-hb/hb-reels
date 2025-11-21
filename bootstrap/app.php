<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Increase PHP limits early for large file uploads
// NOTE: post_max_size and upload_max_filesize cannot be changed with ini_set()
// They must be set in php.ini or .htaccess. These ini_set() calls are for other settings.
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'increase.post.size' => \App\Http\Middleware\IncreasePostSize::class,
        ]);
        
        // Apply to routes that handle large file uploads
        $middleware->web(append: [
            \App\Http\Middleware\IncreasePostSize::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle PostTooLargeException with a more helpful message
        $exceptions->render(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is too large. Maximum allowed size is 200MB. Please compress your video or use a smaller file.',
                    'error' => 'POST_TOO_LARGE',
                ], 413);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'demo_video' => 'The uploaded file is too large. Maximum allowed size is 200MB. Please compress your video or use a smaller file.',
                ]);
        });
        
        // Handle ValidationException for AJAX requests
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'You are not authorized to perform this action.',
                ], 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found.',
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTP method not allowed for this endpoint.',
                ], 405);
            }
        });
        
        // Handle general exceptions for AJAX requests
        $exceptions->render(function (\Exception $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                \Illuminate\Support\Facades\Log::error('AJAX request error', [
                    'error' => $e->getMessage(),
                    'url' => $request->url(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ], 500);
            }
        });
    })->create();
