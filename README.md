# API Response Formatter

A Laravel package that automatically formats API responses to a consistent structure.

## Features

- Automatically formats JSON responses for API routes
- Handles success and error responses consistently
- Supports validation error formatting
- Configurable API route patterns
- Easy to integrate with existing Laravel applications

## Installation

Install the package via Composer:

```bash
composer require sgflores/api-response-formatter
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --provider="SgFlores\ApiResponseFormatter\ApiResponseFormatterServiceProvider" --tag="config"
```

## Configuration

The package comes with a simple configuration file that allows you to customize its behavior:

```php
// config/api-response-formatter.php

return [
    // API route pattern to match
    'api_pattern' => env('API_RESPONSE_FORMATTER_PATTERN', 'api/*'),
    
    // HTTP status codes considered successful
    'success_codes' => [200, 201, 202, 204],
];
```

## Usage

### Method 1: Global Middleware (Recommended)

Add the middleware to your `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... other middleware
    \SgFlores\ApiResponseFormatter\Http\Middleware\FormatResponse::class,
];
```

### Method 2: Route Middleware

Apply the middleware to specific routes:

```php
Route::middleware(['api.format'])->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
});
```

### Method 3: Controller Middleware

Apply the middleware to specific controllers:

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.format');
    }
}
```

## Response Format

The middleware automatically formats responses to this structure:

### Success Response

```json
{
    "success": true,
    "message": "Optional success message",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "data": null,
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Paginated Response

```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": [
        {"id": 1, "name": "Item 1"},
        {"id": 2, "name": "Item 2"}
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 25,
        "last_page": 3
    }
}
```

## Examples

### Before Formatting

```php
// Controller returns
return response()->json(['name' => 'John', 'email' => 'john@example.com']);

// Results in
{
    "name": "John",
    "email": "john@example.com"
}
```

### After Formatting

```json
{
    "success": true,
    "message": null,
    "data": {
        "name": "John",
        "email": "john@example.com"
    }
}
```

## Testing

Run the test suite:

```bash
composer test
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request