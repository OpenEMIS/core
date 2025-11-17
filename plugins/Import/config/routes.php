<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Imports', ['plugin' => 'Import'], function (RouteBuilder $routes) {
    	$routes->connect('/Imports', ['plugin' => 'Import', 'controller' => 'Imports']);
    	$routes->connect('/Imports/:action/*', ['plugin' => 'Import', 'controller' => 'Imports']);
    });
};