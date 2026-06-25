<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Translations', ['plugin' => 'Localization', 'controller' => 'Translations'], function (RouteBuilder $routes) {
        $routes->connect('/');
        $routes->connect('/translate/*', ['action' => 'translate', '_ext' => 'json']);
        $routes->connect('/:action/*');
    });
};
