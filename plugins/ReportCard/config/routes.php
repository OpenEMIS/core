<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/ReportCards', ['plugin' => 'ReportCard'], function (RouteBuilder $routes) {
    $routes->connect('/ReportCards', ['plugin' => 'ReportCard', 'controller' => 'ReportCards']);
    $routes->connect('/ReportCards/:action/*', ['plugin' => 'ReportCard', 'controller' => 'ReportCards']);
});
