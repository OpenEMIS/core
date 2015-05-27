<?php
use Cake\Routing\Router;

Router::scope('/guardians', ['plugin' => 'Guardian'], function ($routes) {
	Router::connect('/guardians', ['plugin' => 'Guardian', 'controller' => 'guardians']);
	Router::connect('/guardians/:action/*', ['plugin' => 'Guardian', 'controller' => 'guardians']);
	$routes->fallbacks('InflectedRoute');
});
