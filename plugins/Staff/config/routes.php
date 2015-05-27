<?php
use Cake\Routing\Router;

Router::scope('/staff', ['plugin' => 'Staff'], function ($routes) {
	Router::connect('/staff', ['plugin' => 'Staff', 'controller' => 'staff']);
	Router::connect('/staff/:action/*', ['plugin' => 'Staff', 'controller' => 'staff']);
	$routes->fallbacks('InflectedRoute');
});
