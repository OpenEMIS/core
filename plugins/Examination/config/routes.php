<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Examinations', ['plugin' => 'Examination'], function (RouteBuilder $routes) {
        $routes->connect('/Examinations', ['plugin' => 'Examination', 'controller' => 'Examinations']);
        $routes->connect('/Examinations/:action/*', ['plugin' => 'Examination', 'controller' => 'Examinations']);
    });
};