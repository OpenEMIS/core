<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Staff', ['plugin' => 'Staff'], function (RouteBuilder $routes) {
        $routes->scope('/Staff', ['controller' => 'Staff'], function (RouteBuilder $route) {
            $route->connect(
                '/',
                ['action' => 'Staff', ]
            );

            // For the main model's action
            $route->connect(
                '/:indexAction',
                [],
                ['indexAction' => 'index','pass' => [0 => 'indexAction']]
            );

            // For controller action version 3
            $route->connect(
                '/:action/*',
                [],
                ['action' => '[a-zA-Z]+']
            );
        });

    });
    $routes->scope('/Staff', ['plugin' => 'Staff'], function (RouteBuilder $routes) {
        $routes->connect('/Staff', ['plugin' => 'Staff', 'controller' => 'Staff']);
        $routes->connect('/Staff/:action/*', ['plugin' => 'Staff', 'controller' => 'Staff']);
        $routes->fallbacks('InflectedRoute');
    });
};