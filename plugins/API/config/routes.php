<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/api', ['plugin' => 'API'], function (RouteBuilder $routes) {
    	$routes->connect('/api', ['plugin' => 'API', 'controller' => 'Api']);
    	$routes->connect('/api/:action/*', ['plugin' => 'API', 'controller' => 'Api']);
    });
};