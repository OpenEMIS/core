<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/CustomFields', ['plugin' => 'CustomField'], function (RouteBuilder $routes) {
    	$routes->connect('/CustomFields', ['plugin' => 'CustomField', 'controller' => 'CustomFields']);
    	$routes->connect('/CustomFields/:action/*', ['plugin' => 'CustomField', 'controller' => 'CustomFields']);
    });
};
