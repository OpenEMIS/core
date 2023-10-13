<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Students', ['plugin' => 'Student'], function (RouteBuilder $routes) {
	$routes->connect('/Students', ['plugin' => 'Student', 'controller' => 'Students']);
	$routes->connect('/Students/:action/*', ['plugin' => 'Student', 'controller' => 'Students']);
});

