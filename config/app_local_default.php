<?php

return [

    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('SECURITY_SALT', ''),
    ],

    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'persistent' => false,
            'host' => 'localhost',
            'port' => '3306',
            'username' => 'root',
            'password' => '',
            'database' => '',
            'encoding' => 'utf8mb4',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => true,
        ],
    ],

    'EmailTransport' => [
        'openemis' => [
            'className' => 'Smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'timeout' => 30,
            'username' => '',
            'password' => '',
            'client' => null,
            'tls' => true,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Email' => [
        'openemis' => [
            'transport' => 'openemis',
            'from' => ['noreply@example.com' => 'DoNotReply'],
        ],
    ]

];