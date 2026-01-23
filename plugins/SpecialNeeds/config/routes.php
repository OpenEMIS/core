<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/SpecialNeeds', ['plugin' => 'SpecialNeeds'], function (RouteBuilder $routes) {
    	$routes->connect('/SpecialNeeds', ['plugin' => 'SpecialNeeds', 'controller' => 'SpecialNeeds']);
    	$routes->connect('/SpecialNeeds/:action/*', ['plugin' => 'SpecialNeeds', 'controller' => 'SpecialNeeds']);
    });
};