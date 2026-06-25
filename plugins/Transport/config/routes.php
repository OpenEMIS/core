<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Transports', ['plugin' => 'Transport'], function (RouteBuilder $routes) {
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