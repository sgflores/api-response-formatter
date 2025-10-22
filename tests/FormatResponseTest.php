<?php

namespace SgFlores\ApiResponseFormatter\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\ApiResponseFormatter\Http\Middleware\FormatResponse;

class FormatResponseTest extends TestCase
{

    #[Test]
    public function it_formats_successful_response_without_standard_format()
    {
        $middleware = new FormatResponse();
        $request = Request::create('/api/test', 'GET');
        
        $response = new JsonResponse(['name' => 'John', 'email' => 'john@example.com'], 200);
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(JsonResponse::class, $result);
        $data = $result->getData(true);
        
        $this->assertTrue($data['success']);
        $this->assertNull($data['message']);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $data['data']);
    }

    #[Test]
    public function it_formats_error_response_without_standard_format()
    {
        $middleware = new FormatResponse();
        $request = Request::create('/api/test', 'GET');
        
        $response = new JsonResponse(['error' => 'Not found'], 404);
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(JsonResponse::class, $result);
        $data = $result->getData(true);
        
        $this->assertFalse($data['success']);
        $this->assertNull($data['message']);
        $this->assertEquals(['error' => 'Not found'], $data['data']);
    }

    #[Test]
    public function it_formats_validation_error_response()
    {
        $middleware = new FormatResponse();
        $request = Request::create('/api/test', 'POST');
        
        $response = new JsonResponse([
            'message' => 'Validation failed',
            'errors' => [
                'email' => ['The email field is required.'],
                'name' => ['The name field is required.']
            ]
        ], 422);
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(JsonResponse::class, $result);
        $data = $result->getData(true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertNull($data['data']);
        $this->assertEquals([
            'email' => ['The email field is required.'],
            'name' => ['The name field is required.']
        ], $data['errors']);
    }

    #[Test]
    public function it_does_not_format_non_api_routes()
    {
        $middleware = new FormatResponse();
        $request = Request::create('/web/test', 'GET');
        
        $response = new JsonResponse(['name' => 'John'], 200);
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(JsonResponse::class, $result);
        $data = $result->getData(true);
        
        // Should not be formatted since it's not an API route
        $this->assertEquals(['name' => 'John'], $data);
        $this->assertArrayNotHasKey('success', $data);
    }

    #[Test]
    public function it_does_not_format_already_formatted_responses()
    {
        $middleware = new FormatResponse();
        $request = Request::create('/api/test', 'GET');
        
        $response = new JsonResponse([
            'success' => true,
            'message' => 'Success',
            'data' => ['name' => 'John']
        ], 200);
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(JsonResponse::class, $result);
        $data = $result->getData(true);
        
        // Should remain unchanged since it's already formatted
        $this->assertEquals([
            'success' => true,
            'message' => 'Success',
            'data' => ['name' => 'John']
        ], $data);
    }

    #[Test]
    public function it_formats_responses_via_http_request()
    {
        $response = $this->get('/api/test');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(['name' => 'Test User'], $data['data']);
    }

    #[Test]
    public function it_formats_error_responses_via_http_request()
    {
        $response = $this->get('/api/test-error');
        
        $response->assertStatus(404);
        $response->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);
        
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals(['error' => 'Test Error'], $data['data']);
    }

    #[Test]
    public function it_formats_validation_responses_via_http_request()
    {
        $response = $this->get('/api/test-validation');
        
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors'
        ]);
        
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertNull($data['data']);
        $this->assertEquals(['field' => ['The field is required.']], $data['errors']);
    }

    #[Test]
    public function it_handles_pagination_responses()
    {
        $middleware = new FormatResponse();
        $request = Request::create('/api/test', 'GET');
        
        $response = new JsonResponse([
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2']
            ],
            'meta' => [
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 25,
                    'last_page' => 3
                ]
            ]
        ], 200);
        
        $result = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });
        
        $this->assertInstanceOf(JsonResponse::class, $result);
        $data = $result->getData(true);
        
        $this->assertTrue($data['success']);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ], $data['data']);
        $this->assertEquals([
            'current_page' => 1,
            'per_page' => 10,
            'total' => 25,
            'last_page' => 3
        ], $data['pagination']);
    }
}
