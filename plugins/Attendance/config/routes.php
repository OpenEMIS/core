<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;
Router::scope('/', function (RouteBuilder $routes) {
    $routes->connect('/Attendances', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
    $routes->connect('/Attendances/:action/*', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
});

// RouteBuilder::scope('/Attendances', ['plugin' => 'Attendance'], function ($routes) {
//     RouteBuilder::connect('/Attendances', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
//     RouteBuilder::connect('/Attendances/:action/*', ['plugin' => 'Attendance', 'controller' => 'Attendances']);
// });
