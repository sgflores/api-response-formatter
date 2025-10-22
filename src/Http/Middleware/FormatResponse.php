<?php

namespace SgFlores\ApiResponseFormatter\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FormatResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Get configuration
        $apiPattern = config('api-response-formatter.api_pattern', 'api/*');
        $successCodes = config('api-response-formatter.success_codes', [200, 201, 202, 204]);

        // Only format JSON responses for API routes
        if ($request->is($apiPattern) && $response instanceof JsonResponse) {
            try {
                $data = $response->getData(true);
                
                // Skip if data is not an array
                if (!is_array($data)) {
                    return $response;
                }
                
                $statusCode = $response->getStatusCode();
                $isSuccess = in_array($statusCode, $successCodes);
                
                // Check if response needs formatting
                if ($this->needsFormatting($data, $isSuccess)) {
                    $formattedData = $this->formatResponseData($data, $isSuccess);
                    $response->setData($formattedData);
                }
            } catch (\Exception $e) {
                // Log error but don't break the response
                Log::warning('FormatResponse middleware error: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Check if response needs formatting
     */
    private function needsFormatting(array $data, bool $isSuccess): bool
    {
        // Response doesn't have standard format
        if (!isset($data['success'])) {
            return true;
        }
        
        // Response has success field but missing data field
        if (!isset($data['data'])) {
            return true;
        }
        
        // Has nested errors in data object that need to be moved to top level
        if (isset($data['data']['errors']) && !isset($data['errors'])) {
            return true;
        }
        
        // Has top-level errors but missing data field
        if (isset($data['errors']) && !isset($data['data'])) {
            return true;
        }
        
        // Check if this is a Laravel pagination response that needs formatting
        if ($this->isLaravelPaginationResponse($data)) {
            return true;
        }
        
        return false;
    }

    /**
     * Format response data according to API standards
     */
    private function formatResponseData(array $data, bool $isSuccess): array
    {
        // Extract message and errors
        $message = $data['message'] ?? null;
        $errors = $data['errors'] ?? null;
        
        // Check for nested errors in data object (common in validation responses)
        if (!$errors && isset($data['data']['errors'])) {
            $errors = $data['data']['errors'];
        }
        
        // Initialize response structure
        $response = [
            'success' => $isSuccess,
            'message' => $message,
            'data' => null
        ];
        
        // Handle error responses
        if ($errors && !empty($errors)) {
            $response['errors'] = $errors;
            // data remains null for error responses
        }
        // Handle success responses
        else {
            // Check if this is a Laravel pagination response
            if ($this->isLaravelPaginationResponse($data)) {
                $formattedPagination = $this->formatLaravelPagination($data);
                $response['data'] = $formattedPagination['data'];
                $response['pagination'] = $formattedPagination['pagination'];
            } else {
                // Extract pagination if present in meta or direct pagination field
                $pagination = $data['meta']['pagination'] ?? $data['pagination'] ?? null;
                
                // Clean data by removing control fields
                $cleanData = $this->cleanData($data);
                $response['data'] = $cleanData;
                
                // Add pagination for paginated responses
                if ($pagination) {
                    $response['pagination'] = $pagination;
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Clean data by removing control fields and empty objects
     */
    private function cleanData(array $data): mixed
    {
        // If the response already has a 'data' key, extract its contents
        if (isset($data['data']) && is_array($data['data'])) {
            $cleanData = $data['data'];
        } else {
            // Remove control fields from the main data
            $cleanData = $data;
            unset(
                $cleanData['message'], 
                $cleanData['errors'], 
                $cleanData['meta'], 
                $cleanData['pagination']
            );
        }
        
        // Remove nested errors from data object
        if (isset($cleanData['errors'])) {
            unset($cleanData['errors']);
        }
        
        // Handle empty data
        if (empty($cleanData)) {
            return null;
        }
        
        // If cleanData only contains null values, return null
        if (count($cleanData) === 1 && reset($cleanData) === null) {
            return null;
        }
        
        return $cleanData;
    }
    
    /**
     * Check if the response is a Laravel pagination response
     */
    private function isLaravelPaginationResponse(array $data): bool
    {
        // Laravel pagination responses have these specific keys
        $paginationKeys = [
            'current_page', 'data', 'first_page_url', 'from', 'last_page',
            'last_page_url', 'links', 'next_page_url', 'path', 'per_page',
            'prev_page_url', 'to', 'total'
        ];
        
        // Check if all pagination keys are present
        $hasPaginationKeys = count(array_intersect_key($data, array_flip($paginationKeys))) >= 8;
        
        // Also check if it has the 'data' key with array content
        $hasDataArray = isset($data['data']) && is_array($data['data']);
        
        return $hasPaginationKeys && $hasDataArray;
    }
    
    /**
     * Format Laravel pagination response into standard API format
     */
    private function formatLaravelPagination(array $data): array
    {
        // Extract the actual data items
        $items = $data['data'] ?? [];
        
        // Build pagination metadata
        $pagination = [
            'current_page' => $data['current_page'] ?? 1,
            'per_page' => $data['per_page'] ?? 15,
            'total' => $data['total'] ?? 0,
            'last_page' => $data['last_page'] ?? 1,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'has_more_pages' => isset($data['next_page_url']) && !is_null($data['next_page_url']),
            'links' => [
                'first' => $data['first_page_url'] ?? null,
                'last' => $data['last_page_url'] ?? null,
                'prev' => $data['prev_page_url'] ?? null,
                'next' => $data['next_page_url'] ?? null,
            ]
        ];
        
        return [
            'data' => $items,
            'pagination' => $pagination
        ];
    }
}
