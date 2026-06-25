<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Textbooks', ['plugin' => 'Textbook'], function (RouteBuilder $routes) {
    	$routes->connect('/Textbooks', ['plugin' => 'Textbook', 'controller' => 'Textbooks']);
    	$routes->connect('/Textbooks/:action/*', ['plugin' => 'Textbook', 'controller' => 'Textbooks']);
    });
};