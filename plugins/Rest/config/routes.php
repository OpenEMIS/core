<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Rest', ['plugin' => 'Rest'], function (RouteBuilder $routes) {
    	$routes->connect('/Rest', ['plugin' => 'Rest', 'controller' => 'Rest']);
    	$routes->connect('/Rest/:action/*', ['plugin' => 'Rest', 'controller' => 'Rest']);
    });
};