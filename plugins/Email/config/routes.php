<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Emails', ['plugin' => 'Email'], function (RouteBuilder $routes) {
    	$routes->connect('/Emails', ['plugin' => 'Email', 'controller' => 'Emails']);
    	$routes->connect('/Emails/:action/*', ['plugin' => 'Email', 'controller' => 'Emails']);
    });
};