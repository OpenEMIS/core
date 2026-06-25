<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Healths', ['plugin' => 'Health'], function (RouteBuilder $routes) {
    	$routes->connect('/Healths', ['plugin' => 'Health', 'controller' => 'Healths']);
    	$routes->connect('/Healths/:action/*', ['plugin' => 'Health', 'controller' => 'Healths']);
    });
};
