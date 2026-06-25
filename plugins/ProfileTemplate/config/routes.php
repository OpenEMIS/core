<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/', ['plugin' => 'ProfileTemplate'], function (RouteBuilder $routes) {
        $routes->connect('/ProfileTemplates', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates']);
        $routes->connect('/ProfileTemplates/:action/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates']);
    });
};