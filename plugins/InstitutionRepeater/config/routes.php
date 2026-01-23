<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/InstitutionRepeaters', ['plugin' => 'InstitutionRepeater'], function (RouteBuilder $routes) {
    	$routes->connect('/InstitutionRepeaters', ['plugin' => 'InstitutionRepeater', 'controller' => 'InstitutionRepeaters']);
    	$routes->connect('/InstitutionRepeaters/:action/*', ['plugin' => 'InstitutionRepeater', 'controller' => 'InstitutionRepeaters']);
    });
};