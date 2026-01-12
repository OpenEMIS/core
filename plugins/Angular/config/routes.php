<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Angular', ['plugin' => 'Angular'], function (RouteBuilder $routes) {
        $routes->connect('/Angular', ['plugin' => 'Angular', 'controller' => 'Angular']);
        $routes->connect('/Angular/:action/*', ['plugin' => 'Angular', 'controller' => 'Angular']);
    });
};
