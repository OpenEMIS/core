<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/StaffCustomFields', ['plugin' => 'StaffCustomField'], function (RouteBuilder $routes) {
    	$routes->connect('/StaffCustomFields', ['plugin' => 'StaffCustomField', 'controller' => 'StaffCustomFields']);
    	$routes->connect('/StaffCustomFields/:action/*', ['plugin' => 'StaffCustomField', 'controller' => 'StaffCustomFields']);
    });
};