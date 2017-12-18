<?php
use Cake\Routing\Router;

Router::scope('/Users', ['plugin' => 'User'], function ($routes) {
    // Router::connect('/Users/:action/*', ['plugin' => 'User', 'controller' => 'Users']);
    $routes->scope('/', ['controller' => 'Users'], function ($route) {
        $route->connect('/:action/*');
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
