<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    /*$routes->scope('/Guardians', ['plugin' => 'Guardian'], function (RouteBuilder $routes) {

        $routes->scope('/:controller', [], function (RouteBuilder $route) {
            $route->connect('/:action',
                [],
                ['action' => '[a-zA-Z]+']
            );

            $route->connect('/:action/*',
                [],
                ['action' => '[a-zA-Z]+']
            );
        });;
    });;*/

    $routes->scope('/Guardian', ['plugin' => 'Guardian'], function (RouteBuilder $routes) {
        // $routes->connect('/Directories', ['plugin' => 'Directory', 'controller' => 'Directories']);
        // $routes->connect('/Directories/:action/*', ['plugin' => 'Directory', 'controller' => 'Directories']);

        $routes->scope('/Guardians', ['controller' => 'Guardians'], function (RouteBuilder $route) {
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
        });;

        $routes->scope('/:controller', [], function (RouteBuilder $route) {
            $route->connect('/:action',
                [],
                ['action' => '[a-zA-Z]+']
            );

            $route->connect('/:action/*',
                [],
                ['action' => '[a-zA-Z]+']
            );
        });;
    });;

    // Fall back route
    $routes->scope('/Guardians', ['plugin' => 'Guardian', 'controller' => 'Guardians'], function (RouteBuilder $route) {
        // For controller action version 3
        $route->connect(
            '/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });
};