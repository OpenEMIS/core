<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/AcademicPeriods', ['plugin' => 'AcademicPeriod'], function (RouteBuilder $routes) {
        $routes->connect('/AcademicPeriods', ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods']);
        $routes->connect('/AcademicPeriods/:action/*', ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods']);
    });
};