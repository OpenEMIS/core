<?php
use Cake\Routing\Router;

Router::scope('/restfull', ['plugin' => 'Restfull'], function ($routes) {

    $routes->extensions(['json', 'xml']);
	$routes->connect(
			'/doc',
			['plugin' => 'Restfull', 'controller' => 'Doc']
		);
	$routes->connect(
			'/:model',
			['plugin' => 'Restfull', 'controller' => 'Restfull', 'action' => 'index', '_method' => 'GET'],
	        [
	            'pass' => ['model'],
	        ]
		);
	$routes->connect(
			'/:model',
			['plugin' => 'Restfull', 'controller' => 'Restfull', 'action' => 'add', '_method' => 'POST'],
	        [
	            'pass' => ['model'],
	        ]
		);

	$routes->connect(
			'/:model/:id',
			['plugin' => 'Restfull', 'controller' => 'Restfull', 'action' => 'view', '_method' => 'GET'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);
	$routes->connect(
			'/:model/:id',
			['plugin' => 'Restfull', 'controller' => 'Restfull', 'action' => 'edit', '_method' => 'PUT'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);
	$routes->connect(
			'/:model/:id',
			['plugin' => 'Restfull', 'controller' => 'Restfull', 'action' => 'delete', '_method' => 'DELETE'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);

});
