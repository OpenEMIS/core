<?php
use Cake\Routing\Router;

Router::scope('/rest', ['plugin' => 'Rest', 'controller' => 'Rest'], function ($routes) {

    $routes->extensions(['json', 'xml']);
	$routes->connect(
			'/:model',
			['plugin' => 'Rest', 'controller' => 'Rest', 'action' => 'index', '_method' => 'GET'],
	        [
	            'pass' => ['model'],
	        ]
		);
	$routes->connect(
			'/:model',
			['plugin' => 'Rest', 'controller' => 'Rest', 'action' => 'add', '_method' => 'POST'],
	        [
	            'pass' => ['model'],
	        ]
		);

	$routes->connect(
			'/:model/:id',
			['plugin' => 'Rest', 'controller' => 'Rest', 'action' => 'view', '_method' => 'GET'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);
	$routes->connect(
			'/:model/:id',
			['plugin' => 'Rest', 'controller' => 'Rest', 'action' => 'edit', '_method' => 'PUT'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);
	$routes->connect(
			'/:model/:id',
			['plugin' => 'Rest', 'controller' => 'Rest', 'action' => 'delete', '_method' => 'DELETE'],
	        [
	            'pass' => ['model', 'id'],
	        ]
		);

});
