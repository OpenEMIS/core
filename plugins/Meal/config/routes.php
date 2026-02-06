<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Meals', ['plugin' => 'Meal'], function (RouteBuilder $routes) {
        $routes->connect('/Meals', ['plugin' => 'Meal', 'controller' => 'Meals']);
        $routes->connect('/Meals/:action/*', ['plugin' => 'Meal', 'controller' => 'Meals']);
    });
};