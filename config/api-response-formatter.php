<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Route Pattern
    |--------------------------------------------------------------------------
    |
    | The pattern used to identify API routes that should be formatted.
    | Default is 'api/*' which matches all routes starting with 'api/'.
    |
    */
    'api_pattern' => env('API_RESPONSE_FORMATTER_PATTERN', 'api/*'),

    /*
    |--------------------------------------------------------------------------
    | Success Status Codes
    |--------------------------------------------------------------------------
    |
    | HTTP status codes that are considered successful responses.
    | Responses with these status codes will have success: true.
    |
    */
    /*
    |----------------------------------------------------------------------
    | Success Status Codes (2xx)
    |----------------------------------------------------------------------
    | List of all HTTP 2xx status codes that are generally considered
    | to indicate success. You may customize as needed.
    |
    | 200 OK, 201 Created, 202 Accepted, 203 Non-Authoritative Information,
    | 204 No Content, 205 Reset Content, 206 Partial Content,
    | 207 Multi-Status (WebDAV), 208 Already Reported (WebDAV),
    | 226 IM Used
    */
    'success_codes' => [
        200, // OK
        201, // Created
        202, // Accepted
        203, // Non-Authoritative Information
        204, // No Content
        205, // Reset Content
        206, // Partial Content
        207, // Multi-Status (WebDAV)
        208, // Already Reported (WebDAV)
        226, // IM Used
    ],
];
