<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/ReportCards', ['plugin' => 'ReportCard'], function (RouteBuilder $routes) {
        $routes->connect('/ReportCards', ['plugin' => 'ReportCard', 'controller' => 'ReportCards']);
        $routes->connect('/ReportCards/:action/*', ['plugin' => 'ReportCard', 'controller' => 'ReportCards']);
    });
};