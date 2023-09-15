<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::scope('/Profiles', ['plugin' => 'Profile'], function ($routes) {
    // Router::connect('/Profiles', ['plugin' => 'Profile', 'controller' => 'Profiles']);
    // Router::connect('/Profiles/:action/*', ['plugin' => 'Profile', 'controller' => 'Profiles']);

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
/*Router::scope('/Profiles', ['plugin' => 'Profile'], function ($routes) {
    $routes->scope('/', ['controller' => 'ProfilesController'], function ($routes) {
        // $routes->extensions(['json']);

        $routes->connect('/:key', ['action' => 'Accounts', '_method' => 'GET'], ['pass' => ['key']]);
    });
});*/
