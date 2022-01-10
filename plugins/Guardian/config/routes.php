<?php
use Cake\Routing\Router;

/*Router::scope('/Guardians', ['plugin' => 'Guardian'], function ($routes) {

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
});*/

Router::scope('/Guardian', ['plugin' => 'Guardian'], function ($routes) {
    // Router::connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
    // Router::connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);

    $routes->scope('/Guardians', ['controller' => 'Guardians'], function ($route) {
        $route->connect(
            '/',
            ['action' => 'Guardians', ]
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
Router::scope('/Guardians', ['plugin' => 'Guardian', 'controller' => 'Guardians'], function ($route) {
    // For controller action version 3
    $route->connect(
        '/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
