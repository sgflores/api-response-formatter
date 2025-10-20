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
        
        // Extract pagination if present
        $pagination = $data['meta']['pagination'] ?? $data['pagination'] ?? null;
        
        // Clean data by removing control fields
        $cleanData = $this->cleanData($data);
        
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
            $response['data'] = $cleanData;
            
            // Add pagination for paginated responses
            if ($pagination) {
                $response['pagination'] = $pagination;
            }
        }
        
        return $response;
    }
    
    /**
     * Clean data by removing control fields and empty objects
     */
    private function cleanData(array $data): mixed
    {
        // Remove control fields
        $cleanData = $data;
        unset(
            $cleanData['message'], 
            $cleanData['errors'], 
            $cleanData['meta'], 
            $cleanData['pagination']
        );
        
        // Remove nested errors from data object
        if (isset($cleanData['data']['errors'])) {
            unset($cleanData['data']['errors']);
        }
        
        // Handle empty data object
        if (isset($cleanData['data']) && empty($cleanData['data'])) {
            $cleanData['data'] = null;
        }
        
        // If cleanData is empty or only contains null values, return null
        if (empty($cleanData) || (count($cleanData) === 1 && reset($cleanData) === null)) {
            return null;
        }
        
        // If cleanData only contains an empty data object, return null
        if (isset($cleanData['data']) && $cleanData['data'] === null && count($cleanData) === 1) {
            return null;
        }
        
        return $cleanData;
    }
}
