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
		}
	);

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

		}
	);

});
