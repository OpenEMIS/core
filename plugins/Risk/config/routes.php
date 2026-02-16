<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Risk', ['plugin' => 'Risk'], function (RouteBuilder $routes) {
        $routes->connect('/Risks', ['plugin' => 'Risk', 'controller' => 'Risks']);
        $routes->connect('/Risks/:action/*', ['plugin' => 'Risk', 'controller' => 'Risks']);
    });
};