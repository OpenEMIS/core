<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Caches', ['plugin' => 'Cache'], function (RouteBuilder $routes) {
    	$routes->connect('/Caches', ['plugin' => 'Cache', 'controller' => 'Caches']);
    	$routes->connect('/Caches/:action/*', ['plugin' => 'Cache', 'controller' => 'Caches']);
    });
};