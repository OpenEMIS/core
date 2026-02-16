<?php
use Cake\Routing\RouteBuilder;
return function (RouteBuilder $routes) {
    $routes->scope('/Educations', ['plugin' => 'Education'], function (RouteBuilder $routes) {
    	$routes->connect('/Educations', ['plugin' => 'Education', 'controller' => 'Educations']);
    	$routes->connect('/Educations/:action/*', ['plugin' => 'Education', 'controller' => 'Educations']);
    });
};
