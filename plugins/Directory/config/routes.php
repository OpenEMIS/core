<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Directory', ['plugin' => 'Directory'], function (RouteBuilder $routes) {
    // Router::connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
    // Router::connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);

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
Router::scope('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories'], function ($route) {
    // For controller action version 3
    $route->connect(
        '/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
