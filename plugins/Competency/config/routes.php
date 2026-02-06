<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Competencies', ['plugin' => 'Competency'], function (RouteBuilder $routes) {
    	$routes->connect('/Competencies', ['plugin' => 'Competency', 'controller' => 'Competencies']);
    	$routes->connect('/Competencies/:action/*', ['plugin' => 'Competency', 'controller' => 'Competencies']);
    });
};