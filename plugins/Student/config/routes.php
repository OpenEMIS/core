<?php
use Cake\Routing\Router;

Router::scope('/students', ['plugin' => 'Student'], function ($routes) {
	Router::connect('/students', ['plugin' => 'Student', 'controller' => 'students']);
	Router::connect('/students/:action/*', ['plugin' => 'Student', 'controller' => 'students']);
	$routes->fallbacks('InflectedRoute');
});
