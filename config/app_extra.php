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
	]
];

