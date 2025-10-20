<?php

namespace SgFlores\ApiResponseFormatter\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SgFlores\ApiResponseFormatter\ApiResponseFormatterServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ApiResponseFormatterServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Set up configuration for testing
        $app['config']->set('api-response-formatter.api_pattern', 'api/*');
        $app['config']->set('api-response-formatter.success_codes', [200, 201, 202, 204]);
    }

    /**
     * Define middleware setup.
     */
    protected function defineMiddleware($middleware): void
    {
        // Register the middleware alias
        $middleware->alias('api.format', \SgFlores\ApiResponseFormatter\Http\Middleware\FormatResponse::class);
    }

    /**
     * Define routes setup.
     */
    protected function defineRoutes($router): void
    {
        // Define test routes with middleware
        $router->middleware(['api.format'])->group(function ($router) {
            $router->get('/api/test', function () {
                return response()->json(['name' => 'Test User']);
            });
            
            $router->get('/api/test-error', function () {
                return response()->json(['error' => 'Test Error'], 404);
            });
            
            $router->get('/api/test-validation', function () {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['field' => ['The field is required.']]
                ], 422);
            });
        });
    }
}
