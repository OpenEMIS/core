<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Map', ['plugin' => 'Map'], function (RouteBuilder $routes) {
    	$routes->connect('/Map', ['plugin' => 'Map', 'controller' => 'Map']);
    	$routes->connect('/Map/:action/*', ['plugin' => 'Map', 'controller' => 'Map']);
    });
};