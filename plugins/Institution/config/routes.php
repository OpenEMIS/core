<?php
use Cake\Routing\Router;

Router::scope('/Institutions', ['plugin' => 'Institution'], function ($routes) {
	Router::connect('/Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions']);
	Router::connect('/Institutions/:action/*', ['plugin' => 'Institution', 'controller' => 'Institutions']);
	$routes->fallbacks('InflectedRoute');
});
