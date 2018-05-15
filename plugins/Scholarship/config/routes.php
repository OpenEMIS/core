<?php
use Cake\Routing\Router;

Router::scope('/Scholarship', ['plugin' => 'Scholarship'], function ($routes) {
    $routes->scope('/Scholarships', ['controller' => 'Scholarships'], function ($route) {
        $route->connect(
            '/',
            ['action' => 'Scholarships', ]
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
Router::scope('/Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships'], function ($route) {
    // For controller action version 3
    $route->connect(
        '/:action/*',
        [],
        ['action' => '[a-zA-Z]+']
    );
});
