<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/StudentCustomFields', ['plugin' => 'StudentCustomField'], function (RouteBuilder $routes) {
    	$routes->connect('/StudentCustomFields', ['plugin' => 'StudentCustomField', 'controller' => 'StudentCustomFields']);
    	$routes->connect('/StudentCustomFields/:action/*', ['plugin' => 'StudentCustomField', 'controller' => 'StudentCustomFields']);
    });
};