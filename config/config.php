<?php

/*
 * Configuration for Topol Email Templates package
 */
return [
    /*
     * API endpoint to fetch email templates
     */
    'api_url' => 'https://api.topol.io',

    /*
     * API key for authentication
     */
    'api_key' => env('TOPOL_API_KEY'),

    /*
     * Timeout for API requests in seconds
     */
    'timeout' => env('TOPOL_API_TIMEOUT', 30),

    /*
     * Cache settings for email templates
     */
    'cache' => [
        'enabled' => env('TOPOL_CACHE_ENABLED', true),
        'ttl' => env('TOPOL_CACHE_TTL', 3600), // 1 hour in seconds
        'prefix' => 'topol_email_template_',
    ],

    /*
     * Enable/disable the package
     */
    'enabled' => env('TOPOL_ENABLED', true),
];
