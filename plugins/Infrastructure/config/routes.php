<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Infrastructures', ['plugin' => 'Infrastructure'], function (RouteBuilder $routes) {
    	$routes->connect('/Infrastructures', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures']);
    	$routes->connect('/Infrastructures/:action/*', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures']);
    });
};