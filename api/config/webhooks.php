<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | POCOR-9257: Configuration for async webhook processing
    |
    */

    // HTTP request timeout (seconds)
    'timeout' => env('WEBHOOK_TIMEOUT', 30),

    // Connection timeout (seconds)
    'connect_timeout' => env('WEBHOOK_CONNECT_TIMEOUT', 10),

    // Verify SSL certificates
    'verify_ssl' => env('WEBHOOK_VERIFY_SSL', true),

    // Maximum retries per webhook
    'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),

    // Batch size for queue processing
    'batch_size' => env('WEBHOOK_BATCH_SIZE', 100),

    // Enable/disable webhook processing
    'enabled' => env('WEBHOOK_ENABLED', true),

    // Log successful webhooks (for debugging)
    'log_success' => env('WEBHOOK_LOG_SUCCESS', false),

    // HMAC signature algorithm
    'hmac_algorithm' => env('WEBHOOK_HMAC_ALGORITHM', 'sha256'),
];
