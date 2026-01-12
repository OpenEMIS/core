<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Directory', ['plugin' => 'Directory'], function (RouteBuilder $routes) {
        // $routes->connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
        // $routes->connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);

        $routes->scope('/Directories', ['controller' => 'Directories'], function (RouteBuilder $routes) {
            $routes->connect(
                '/',
                ['action' => 'Directories', ]
            );

            // For controller action version 3
            $routes->connect(
                '/:action/*',
                [],
                ['action' => '[a-zA-Z]+']
            );
        });

        $routes->scope('/:controller', [], function (RouteBuilder $routes) {
            $routes->connect('/:action',
                [],
                ['action' => '[a-zA-Z]+']
            );

            $routes->connect('/:action/*',
                [],
                ['action' => '[a-zA-Z]+']
            );
        });
    });

    // Fall back route
    $routes->scope('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories'], function (RouteBuilder $route) {
        // For controller action version 3
        $route->connect(
            '/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });
};