<?php
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

$privateKeyPath = CONFIG . 'private.key';
$publicKeyPath = CONFIG . 'public.key';

$privateKeyFile = new File($privateKeyPath);
$publicKeyFile = new File($publicKeyPath);
$privateKey = $privateKeyFile->read();
$publicKey = $publicKeyFile->read();

return [
    'Error' => [
        // Application specific error handler
        'exceptionRenderer' => 'App\Error\AppExceptionRenderer'
    ],

    'Cache' => [
        // Application specific labels cache
        'labels' => [
            'className' => 'File',
            'path' => CACHE,
            'probability' => 0,
            'duration' => '+1 month',
            'groups' => ['labels'],
            'url' => env('CACHE_DEFAULT_URL', null)
        ]
    ],


    'Application' => [
        // Generate a private and public key pair using the command line by executing "openssl genrsa -out private.key 1024" and "openssl rsa -in private.key -pubout -out public.key"
        'private' => [
            'key' => $privateKey
        ],
        'public' => [
            'key' => $publicKey
        ]
    ],

    'EmailTransport' => [
        'openemis' => [
            'className' => 'Smtp',
            // The following keys are used in SMTP transports
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'timeout' => 30,
            'username' => 'app@kordit.com',
            'password' => '',
            'client' => null,
            'tls' => true,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Email' => [
        'openemis' => [
            'transport' => 'openemis',
            'from' => ['app@kordit.com' => 'DoNotReply'],
            //'charset' => 'utf-8',
            //'headerCharset' => 'utf-8',
        ],
    ]
];
