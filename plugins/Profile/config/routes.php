<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Profiles', ['plugin' => 'Profile'], function (RouteBuilder $routes) {
        // $routes->connect('/Profiles', ['plugin' => 'Profile', 'controller' => 'Profiles']);
        // $routes->connect('/Profiles/:action/*', ['plugin' => 'Profile', 'controller' => 'Profiles']);

        $routes->scope('/:controller', [], function (RouteBuilder $route) {
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
};