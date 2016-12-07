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


	// You may generate your own sha256 key value or use an online sha256 generator to generate your key e.g. http://www.xorbin.com/tools/sha256-hash-calculator
	'Application' => [
		'key' => 'e0dbb83e2a4f13046ab70a8a9e7254965eb9b97e31485428b02317bb24032fbc'
	],
];

