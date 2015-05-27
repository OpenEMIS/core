<?php
use Cake\Routing\Router;

Router::scope('/Staff', ['plugin' => 'Staff'], function ($routes) {
	Router::connect('/Staff', ['plugin' => 'Staff', 'controller' => 'Staff']);
	Router::connect('/Staff/:action/*', ['plugin' => 'Staff', 'controller' => 'Staff']);
	$routes->fallbacks('InflectedRoute');
});
