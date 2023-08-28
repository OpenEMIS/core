<?php
use Cake\Routing\Router;

Router::scope('/Reports', ['plugin' => 'Report'], function ($routes) {
	$routes->connect('/Reports', ['plugin' => 'Report', 'controller' => 'Reports', 'action'=>'index']);
	$routes->connect('/Reports/:action/*', ['plugin' => 'Report', 'controller' => 'Reports']);
});
