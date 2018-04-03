<?php
use Cake\Routing\Router;

Router::scope('/Scholarship', ['plugin' => 'Scholarship'], function ($routes) {
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
