<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Theme', ['plugin' => 'Theme'], function (RouteBuilder $routes) {
        $routes->connect('/Theme', ['plugin' => 'Theme', 'controller' => 'Themes']);
        $routes->connect('/Theme/:action/*', ['plugin' => 'Theme', 'controller' => 'Themes']);
    });
};