<?php

namespace SgFlores\ApiResponseFormatter\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            $data = $response->getData(true);
            $statusCode = $response->getStatusCode();
            $isSuccess = in_array($statusCode, $successCodes);
            
            // Check if response needs formatting
            $needsFormatting = $this->needsFormatting($data, $isSuccess);
            
            if ($needsFormatting) {
                $formattedData = $this->formatResponseData($data, $isSuccess);
                $response->setData($formattedData);
            }
        }

        return $response;
    }

    /**
     * Check if response needs formatting
     */
    private function needsFormatting(array $data, bool $isSuccess): bool
    {
        // If response doesn't have our standard format at all
        if (!isset($data['success'])) {
            return true;
        }
        
        // If response has success but no data field
        if (isset($data['success']) && !isset($data['data'])) {
            return true;
        }
        
        // If response has nested errors in data object (like validation errors)
        if (isset($data['data']['errors']) && !isset($data['errors'])) {
            return true;
        }
        
        // If response has errors at top level but they should be in data
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
        // Extract message and errors from the original data
        $message = $data['message'] ?? null;
        $errors = $data['errors'] ?? null;
        
        // Check for nested errors in data object (common in validation responses)
        if (!$errors && isset($data['data']['errors'])) {
            $errors = $data['data']['errors'];
        }
        
        // Clean up the data - remove message and errors from data if they exist
        $cleanData = $data;
        unset($cleanData['message'], $cleanData['errors']);
        
        // Remove nested errors from data if they exist
        if (isset($cleanData['data']['errors'])) {
            unset($cleanData['data']['errors']);
        }
        
        // Handle pagination if present
        $pagination = null;
        if (isset($cleanData['meta']['pagination']) || isset($cleanData['pagination'])) {
            $pagination = $cleanData['meta']['pagination'] ?? $cleanData['pagination'] ?? null;
            unset($cleanData['meta'], $cleanData['pagination']);
        }
        
        // If cleanData is empty or only contains null values, set to null
        if (empty($cleanData) || (count($cleanData) === 1 && reset($cleanData) === null)) {
            $cleanData = null;
        }
        
        // If data object is empty after removing errors, set to null
        if (isset($cleanData['data']) && empty($cleanData['data'])) {
            $cleanData['data'] = null;
        }
        
        $formattedData = [
            'success' => $isSuccess,
            'message' => $message,
            'data' => $cleanData
        ];
        
        // Handle validation errors - put them at top level for errors
        if ($errors && !empty($errors)) {
            $formattedData['errors'] = $errors;
            $formattedData['data'] = null;
        }
        // Handle success responses with pagination
        elseif ($isSuccess && $pagination) {
            $formattedData['data'] = $cleanData;
            $formattedData['pagination'] = $pagination;
        }
        // Keep message at top level for all responses
        // No need to move message into data object
        
        return $formattedData;
    }
}
