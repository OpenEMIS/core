<?php
use Cake\Routing\Router;

Router::scope('/institutions', ['plugin' => 'Institution'], function ($routes) {
	Router::connect('/institutions', ['plugin' => 'Institution', 'controller' => 'Institutions']);
	Router::connect('/institutions/:action/*', ['plugin' => 'Institution', 'controller' => 'Institutions']);
	$routes->fallbacks('InflectedRoute');
});
