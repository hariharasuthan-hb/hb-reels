<?php

namespace HbReels\EventReelGenerator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use HbReels\EventReelGenerator\Console\GenerateReelCommand;

class EventReelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/eventreel.php',
            'eventreel'
        );

        // Register services as singletons
        $this->app->singleton(\HbReels\EventReelGenerator\Services\OCRService::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\AIService::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\PexelsService::class);
        $this->app->singleton(\HbReels\EventReelGenerator\Services\VideoRenderer::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/eventreel.php' => config_path('eventreel.php'),
        ], 'eventreel-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/eventreel'),
        ], 'eventreel-views');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'eventreel');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateReelCommand::class,
            ]);
        }

        // Load routes
        $this->loadRoutes();
    }

    /**
     * Load package routes.
     * Routes require authentication and active subscription (unless user is admin).
     */
    protected function loadRoutes(): void
    {
        Route::middleware(['web', 'auth', 'check.subscription'])
            ->prefix(config('eventreel.route_prefix', 'event-reel'))
            ->name(config('eventreel.route_name_prefix', 'eventreel.'))
            ->group(function () {
                require __DIR__ . '/../routes/web.php';
            });
    }
}

