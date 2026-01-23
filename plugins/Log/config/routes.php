<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Logs', ['plugin' => 'Log'], function (RouteBuilder $routes) {
    	$routes->connect('/Logs', ['plugin' => 'Log', 'controller' => 'Logs']);
    	$routes->connect('/Logs/:action/*', ['plugin' => 'Log', 'controller' => 'Logs']);
    });
};