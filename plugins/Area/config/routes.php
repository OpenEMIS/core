<?php
use Cake\Routing\Router;

Router::scope('/areas', ['plugin' => 'Area'], function ($routes) {
	Router::connect('/areas', ['plugin' => 'Area', 'controller' => 'Areas']);
	Router::connect('/areas/:action/*', ['plugin' => 'Area', 'controller' => 'Areas']);
	$routes->fallbacks('InflectedRoute');
});
