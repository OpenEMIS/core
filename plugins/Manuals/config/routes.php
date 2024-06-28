<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/', ['plugin' => 'Manuals'], function (RouteBuilder $routes) {
    $routes->connect('/Manuals', ['plugin' => 'Manuals', 'controller' => 'Manuals']);
    $routes->connect('/Manuals/:action/*', ['plugin' => 'Manuals', 'controller' => 'Manuals']);
});
