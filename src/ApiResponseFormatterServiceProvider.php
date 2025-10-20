<?php

namespace SgFlores\ApiResponseFormatter;

use Illuminate\Support\ServiceProvider;
use SgFlores\ApiResponseFormatter\Http\Middleware\FormatResponse;

class ApiResponseFormatterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-response-formatter.php',
            'api-response-formatter'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/api-response-formatter.php' => config_path('api-response-formatter.php'),
        ], 'config');

        // Register middleware
        $this->app['router']->aliasMiddleware('api.format', FormatResponse::class);
    }
}
