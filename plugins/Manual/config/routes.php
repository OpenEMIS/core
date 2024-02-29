<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Manuals', ['plugin' => 'Manual'], function (RouteBuilder $routes) {
    Router::connect('/Manuals', ['plugin' => 'Manual', 'controller' => 'Manuals']);
    Router::connect('/Manuals/:action/*', ['plugin' => 'Manual', 'controller' => 'Manuals']);
});
