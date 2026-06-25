<?php
use Cake\Routing\RouteBuilder;
return function (RouteBuilder $routes) {
    $routes->scope('/Student', ['plugin' => 'Student'], function (RouteBuilder $routes) {
        $routes->scope('/Students', ['controller' => 'Students'], function (RouteBuilder $route) {
            $route->connect(
                '/',
                ['action' => 'Students', ]
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
        });;
    });;
    $routes->scope('/Students', ['plugin' => 'Student'], function (RouteBuilder $routes) {
    	$routes->connect('/Students', ['plugin' => 'Student', 'controller' => 'Students']);
    	$routes->connect('/Students/:action/*', ['plugin' => 'Student', 'controller' => 'Students']);
    });;
    $routes->scope('/', function (RouteBuilder $routes) {
        $routes->connect('/student/students/index/*', ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
    });
};
