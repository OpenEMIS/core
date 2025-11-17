<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Alerts', ['plugin' => 'Alert'], function (RouteBuilder $routes) {
    	$routes->connect('/Alerts', ['plugin' => 'Alert', 'controller' => 'Alerts']);
    	$routes->connect('/Alerts/:action/*', ['plugin' => 'Alert', 'controller' => 'Alerts']);
    });
};
