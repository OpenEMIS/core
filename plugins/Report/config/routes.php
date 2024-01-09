<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

$routes->scope('/', ['plugin' => 'Report'], function (RouteBuilder $routes) {
	$routes->connect('/Reports', ['plugin' => 'Report', 'controller' => 'Reports', 'action'=>'index']);
	$routes->connect('/Reports/:action/*', ['plugin' => 'Report', 'controller' => 'Reports']);
});
