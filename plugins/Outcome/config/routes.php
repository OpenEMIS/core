<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Outcomes', ['plugin' => 'Outcome', 'controller' => 'Outcomes'], function (RouteBuilder $routes) {
        $routes->connect('/:action/*');
    });
};