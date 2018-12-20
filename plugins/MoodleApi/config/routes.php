<?php
use Cake\Routing\Router;

Router::scope('/MoodleApi', ['plugin' => 'MoodleApi'], function ($routes) {
    $routes->scope('/log', ['controller' => 'MoodleApiLog'], function ($route) {
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
