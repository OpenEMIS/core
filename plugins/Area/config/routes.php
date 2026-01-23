<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Areas', ['plugin' => 'Area'], function (RouteBuilder $routes) {
        $routes->connect('/Areas', ['plugin' => 'Area', 'controller' => 'Areas']);
        $routes->connect('/Areas/:action/*', ['plugin' => 'Area', 'controller' => 'Areas']);
    });
};