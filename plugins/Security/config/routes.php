<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Securities', ['plugin' => 'Security'], function (RouteBuilder $routes) {
        $routes->connect('/Securities', ['plugin' => 'Security', 'controller' => 'Securities']);
        $routes->connect('/Securities/:action/*', ['plugin' => 'Security', 'controller' => 'Securities']);
    });
};