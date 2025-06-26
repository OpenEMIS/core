<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Caches', ['plugin' => 'Cache'], function (RouteBuilder $routes) {
	$routes->connect('/Caches', ['plugin' => 'Cache', 'controller' => 'Caches']);
	$routes->connect('/Caches/:action/*', ['plugin' => 'Cache', 'controller' => 'Caches']);
});
