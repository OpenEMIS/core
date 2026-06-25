<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Assessments', ['plugin' => 'Assessment'], function (RouteBuilder $routes) {
        $routes->connect('/Assessments', ['plugin' => 'Assessment', 'controller' => 'Assessments']);
        $routes->connect('/Assessments/:action/*', ['plugin' => 'Assessment', 'controller' => 'Assessments']);
    });
};