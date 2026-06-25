<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/', function (RouteBuilder $routes) {
        $routes->connect('/Attendances', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
        $routes->connect('/Attendances/:action/*', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
    });
};