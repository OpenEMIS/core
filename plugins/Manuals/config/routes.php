<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Manuals/', ['plugin' => 'Manuals'], function (RouteBuilder $routes) {
        $routes->connect('/Manuals', ['plugin' => 'Manuals', 'controller' => 'Manuals']);
        $routes->connect('/Manuals/:action/*', ['plugin' => 'Manuals', 'controller' => 'Manuals']);
    });
};