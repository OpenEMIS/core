<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Users', ['plugin' => 'User'], function (RouteBuilder $routes) {
        // $routes->connect('/Users/:action/*', ['plugin' => 'User', 'controller' => 'Users']);
        $routes->scope('/', ['controller' => 'Users'], function (RouteBuilder $route) {
            $route->connect('/:action/*');
        });
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
