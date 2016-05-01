<?php
use Cake\Routing\Router;

Router::scope('/restful', ['plugin' => 'Restful'], function ($routes) {

	$routes->scope('/doc', ['controller' => 'Doc'], function ($routes) {
		$routes->connect( '/', ['action' => 'index']);
		$routes->connect( '/index', ['action' => 'index']);
		$routes->connect( '/listing', ['action' => 'listing']);
		$routes->connect( '/viewing', ['action' => 'viewing']);
		$routes->connect( '/adding', ['action' => 'adding']);
		$routes->connect( '/editing', ['action' => 'editing']);
		$routes->connect( '/deleting', ['action' => 'deleting']);
		$routes->connect( '/curl', ['action' => 'curl']);
	});

	$routes->scope('/', ['controller' => 'Restful'], function ($routes) {
	    $routes->extensions(['json', 'xml']);
		$routes->connect( '/', ['action' => 'nothing']);
		$routes->connect( '/:model',
			['action' => 'index', '_method' => 'GET'],
	        ['pass' => ['model']]
		);
		$routes->connect( '/:model',
			['action' => 'add', '_method' => 'POST'],
	        ['pass' => ['model']]
		);
		$routes->connect( '/:model/:id',
			['action' => 'view', '_method' => 'GET'],
	        ['pass' => ['model', 'id']]
		);
		$routes->connect( '/:model/:id',
			['action' => 'edit', '_method' => 'PUT'],
	        ['pass' => ['model', 'id']]
		);
		$routes->connect( '/:model/:id',
			['action' => 'delete', '_method' => 'DELETE'],
	        ['pass' => ['model', 'id']]
		);

		$routes->connect('/_session/:key', ['action' => 'check', '_method' => 'CHECK'], ['pass' => ['key']]);
		$routes->connect('/_session/:key', ['action' => 'read', '_method' => 'GET'], ['pass' => ['key']]);
		$routes->connect('/_session', ['action' => 'write', '_method' => 'POST']);
		$routes->connect('/_session', ['action' => 'delete', '_method' => 'DELETE']);
	});

	$routes->scope('/session', ['controller' => 'Session'], function ($routes) {
	    $routes->extensions(['json']);

		$routes->connect('/:key', 	['action' => 'check', '_method' => 'CHECK'], ['pass' => ['key']]);
		$routes->connect('/:key', 	['action' => 'read', '_method' => 'GET'], ['pass' => ['key']]);
		$routes->connect('/', 		['action' => 'write', '_method' => 'POST']);
		$routes->connect('/:key', 	['action' => 'delete', '_method' => 'DELETE'], ['pass' => ['key']]);
	});
});

Router::scope('/session', ['plugin' => 'Restful'], function ($routes) {
	$routes->scope('/', ['controller' => 'Session'], function ($routes) {
	    $routes->extensions(['json']);

		$routes->connect('/:key', 	['action' => 'check', '_method' => 'CHECK'], ['pass' => ['key']]);
		$routes->connect('/:key', 	['action' => 'read', '_method' => 'GET'], ['pass' => ['key']]);
		$routes->connect('/', 		['action' => 'write', '_method' => 'POST']);
		$routes->connect('/:key', 	['action' => 'delete', '_method' => 'DELETE'], ['pass' => ['key']]);
	});
});
