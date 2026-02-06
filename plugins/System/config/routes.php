<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Systems', ['plugin' => 'System'], function (RouteBuilder $routes) {
        $routes->connect('/Systems', ['plugin' => 'System', 'controller' => 'Systems']);
        $routes->connect('/Systems/:action/*', ['plugin' => 'System', 'controller' => 'Systems']);
    });
};
