<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

$routes->scope('/Staff', ['plugin' => 'Staff'], function (RouteBuilder $routes) {
    $routes->scope('/Staff', ['controller' => 'Staff'], function (RouteBuilder $route) {
        $route->connect(
            '/',
            ['action' => 'Staff', ]
        );

        // For the main model's action
        $route->connect(
            '/:indexAction',
            [],
            ['indexAction' => 'index','pass' => [0 => 'indexAction']]
        );

        // For controller action version 3
        $route->connect(
            '/:action/*',
            [],
            ['action' => '[a-zA-Z]+']
        );
    });

});
Router::scope('/Staff', ['plugin' => 'Staff'], function ($routes) {
	Router::connect('/Staff', ['plugin' => 'Staff', 'controller' => 'Staff']);
	Router::connect('/Staff/:action/*', ['plugin' => 'Staff', 'controller' => 'Staff']);
	$routes->fallbacks('InflectedRoute');
});
 