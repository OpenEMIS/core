<?php
use Cake\Routing\Router;

Router::scope('/Directory', ['plugin' => 'Directory'], function ($routes) {
    // Router::connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
    // Router::connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);

    $routes->scope('/Directories', ['controller' => 'Directories'], function ($route) {
        $route->connect(
            '/',
            ['action' => 'Directories', ]
        );

        // For controller action version 3
        $route->connect(
            '/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });

    $routes->scope('/:controller', [], function ($route) {
        $route->connect('/:action',
            [],
            ['action' => '[a-zA-Z]+']
        );

        $route->connect('/:action/*',
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
