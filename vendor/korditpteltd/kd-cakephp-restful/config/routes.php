<?php
use Cake\Routing\Router;

Router::scope('/restful', ['plugin' => 'Restful'], function ($routes) {

    $routes->extensions(['json', 'xml']);
	$routes->connect(
			'/',
			['plugin' => 'Restful', 'controller' => 'Doc']
		);
	$routes->connect(
			'/doc',
			['plugin' => 'Restful', 'controller' => 'Doc']
		);
	$routes->connect(
			'/:model',
			['plugin' => 'Restful', 'controller' => 'Restful', 'action' => 'index', '_method' => 'GET'],
	        [
	            'pass' => ['model'],
	        ]
		);
	$routes->connect(
			'/:model',
			['plugin' => 'Restful', 'controller' => 'Restful', 'action' => 'add', '_method' => 'POST'],
	        [
	            'pass' => ['model'],
	        ]
		);

	$routes->connect(
			'/:model/:id',
			['plugin' => 'Restful', 'controller' => 'Restful', 'action' => 'view', '_method' => 'GET'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);
	$routes->connect(
			'/:model/:id',
			['plugin' => 'Restful', 'controller' => 'Restful', 'action' => 'edit', '_method' => 'PUT'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);
	$routes->connect(
			'/:model/:id',
			['plugin' => 'Restful', 'controller' => 'Restful', 'action' => 'delete', '_method' => 'DELETE'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);

});
