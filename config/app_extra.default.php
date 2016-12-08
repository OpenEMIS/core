<?php
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
];

