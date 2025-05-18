<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

use Cake\Routing\Route\DashedRoute;

$routes->scope('/Scholarship', ['plugin' => 'Scholarship'], function (RouteBuilder $routes) {
    $routes->scope('/Scholarships', ['controller' => 'Scholarships'], function ($route) {
        $route->connect('/', ['action' => 'Scholarships']);

        // For controller action version 3
        $route->connect('/:action/*', [], ['action' => '[a-zA-Z]+']);
    });

    $routes->scope('/:controller', function ($route) {
        $route->connect('/:action', [], ['action' => '[a-zA-Z]+']);
        $route->connect('/:action/*', [], ['action' => '[a-zA-Z]+']);
    });
});

// Fall back route
$routes->scope('/Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships'], function (RouteBuilder $route) {
    // For controller action version 3
    $route->connect('/:action/*', [], ['action' => '[a-zA-Z]+']);
});

// Additional routes can be added here if needed.

