<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Configurations', ['plugin' => 'Configuration'], function (RouteBuilder $routes) {
    	$routes->connect('/Configurations', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
    	$routes->connect('/Configurations/:action/*', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
    });
};