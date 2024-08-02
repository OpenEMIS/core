<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Risk', ['plugin' => 'Risk'], function (RouteBuilder $routes) {
    $routes->connect('/Risks', ['plugin' => 'Risk', 'controller' => 'Risks']);
    $routes->connect('/Risks/:action/*', ['plugin' => 'Risk', 'controller' => 'Risks']);
});
