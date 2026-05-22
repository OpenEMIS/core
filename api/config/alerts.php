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
    | Process Limit (throttle)
    |--------------------------------------------------------------------------
    |
    | Maximum number of alerts processed per cron run (every minute).
    | Default 20 = up to 1,200 messages/hour, safe for free-tier mail/SMS
    | providers that cap at ~20 messages/minute.
    | Lower this further if the system is sending too many messages too fast
    | — no code change required, just update .env and run config:cache.
    | Set to 0 to pause processing entirely (queue accumulates).
    |
    */
    'process_limit' => env('ALERTS_PROCESS_LIMIT', 20),

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
