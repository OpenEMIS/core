<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Error', ['plugin' => 'Error'], function (RouteBuilder $routes) {
    	$routes->connect('/Errors', ['plugin' => 'Error', 'controller' => 'Errors']);
    	$routes->connect('/Errors/:action/*', ['plugin' => 'Error', 'controller' => 'Errors']);
    });
};