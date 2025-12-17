# API Response Formatter - Release Notes

## 🎉 Version 1.0.0 - Initial Release

**Release Date:** December 25, 2025

### Overview

**API Response Formatter** is a lightweight Laravel package that automatically formats API responses to a consistent structure. It ensures all your API endpoints return responses in a standardized format, making it easier for frontend applications to handle responses consistently.

### ✨ Key Features

- **🔄 Automatic Formatting** - Automatically formats JSON responses for API routes
- **✅ Consistent Structure** - Handles success and error responses consistently
- **📝 Validation Error Support** - Supports Laravel validation error formatting
- **⚙️ Configurable Routes** - Configurable API route patterns via configuration
- **🚀 Easy Integration** - Zero configuration required for basic setup
- **🔒 Type Safety** - Full type hints and IDE support
- **📦 Laravel Auto-Discovery** - Automatic service provider registration

### 📋 Requirements

- **PHP:** ^8.1
- **Laravel:** ^10.0|^11.0|^12.0
- **Dependencies:**
  - `illuminate/support` ^10.0|^11.0|^12.0

### 🚀 Installation

```bash
composer require sgflores/api-response-formatter
```

### 📖 Quick Start

#### Method 1: Global Middleware (Recommended)

Add the middleware to your `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... other middleware
    \SgFlores\ApiResponseFormatter\Http\Middleware\FormatResponse::class,
];
```

#### Method 2: Route Middleware

Apply the middleware to specific routes:

```php
Route::middleware(['api.format'])->group(function () {
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
});
```

#### Method 3: Controller Middleware

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

### 🎯 What's Included

#### Core Components

- **FormatResponse Middleware** - Automatically formats API responses
- **ApiResponseFormatterServiceProvider** - Laravel service provider with auto-discovery
- **Configuration File** - Customizable settings for route patterns and success codes

### 📚 Response Format

The middleware automatically formats responses to a consistent structure:

#### Success Response

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

#### Error Response

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

#### Paginated Response

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

### 🔧 Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --provider="SgFlores\ApiResponseFormatter\ApiResponseFormatterServiceProvider" --tag="config"
```

Default configuration (`config/api-response-formatter.php`):

```php
return [
    // API route pattern to match
    'api_pattern' => env('API_RESPONSE_FORMATTER_PATTERN', 'api/*'),
    
    // HTTP status codes considered successful
    'success_codes' => [200, 201, 202, 204],
];
```

### 🎨 Usage Examples

#### Before Formatting

```php
// Controller returns
return response()->json(['name' => 'John', 'email' => 'john@example.com']);

// Results in
{
    "name": "John",
    "email": "john@example.com"
}
```

#### After Formatting

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

#### With Validation Errors

```php
// Laravel validation error
return response()->json(['errors' => ['email' => ['The email field is required.']]], 422);

// Automatically formatted to
{
    "success": false,
    "message": "Validation failed",
    "data": null,
    "errors": {
        "email": ["The email field is required."]
    }
}
```

#### With Pagination

```php
// Laravel paginated response
return response()->json($users);

// Automatically formatted to include pagination metadata
{
    "success": true,
    "message": null,
    "data": [...],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 100,
        "last_page": 10
    }
}
```

### 🔍 How It Works

1. **Middleware Interception** - The middleware intercepts all API responses
2. **Response Detection** - Checks if the response matches the configured API pattern
3. **Format Detection** - Determines if the response is a success or error
4. **Structure Transformation** - Wraps the response in the standard format
5. **Metadata Addition** - Adds pagination metadata if applicable

### 🧪 Testing

Run the test suite:

```bash
composer test
```

### 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

### 👨‍💻 Author

**sgflores**
- Email: floresopic@gmail.com

### 🙏 Acknowledgments

Built with ❤️ for the Laravel community.

---

**Made with ❤️ for the Laravel community**

