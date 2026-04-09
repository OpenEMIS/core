<?php

// POCOR-9509: Configuration for asynchronous alerts queue system
return [
    /*
    |--------------------------------------------------------------------------
    | Maximum Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Number of times to retry sending a failed alert before marking it as
    | permanently failed (status = -1).
    |
    */
    'max_retries' => env('ALERTS_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Twilio SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending SMS alerts via Twilio API.
    | Set TWILIO_ENABLED=true in .env to enable SMS sending.
    |
    */
    'twilio' => [
        'enabled' => env('TWILIO_ENABLED', false),
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Recipient Filtering
    |--------------------------------------------------------------------------
    |
    | In non-production environments, restrict alert delivery to whitelisted
    | recipients to prevent accidental sends to real users during testing.
    |
    */
    'test_recipients' => [
        'email' => array_filter(explode(',', env('ALERTS_TEST_EMAILS', ''))),
        'sms' => array_filter(explode(',', env('ALERTS_TEST_PHONES', ''))),
    ],
];
