<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Translations', ['plugin' => 'Localization', 'controller' => 'Translations'], function (RouteBuilder $routes) {
    $routes->connect('/');
    $routes->connect('/translate/*', ['action' => 'translate', '_ext' => 'json']);
    $routes->connect('/:action/*');
});
