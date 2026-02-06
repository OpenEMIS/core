<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/MoodleApi', ['plugin' => 'MoodleApi'], function (RouteBuilder $routes) {
        $routes->scope('/log', ['controller' => 'MoodleApiLog'], function (RouteBuilder $route) {
            $route->connect(
                '/',
                ['action' => 'index']
            );
    
            $route->connect(
                '/:action/*',
                [],
                ['action' => '[a-zA-Z]+']
            );
        });
    });
};