<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::plugin('OpenEmis', function (RouteBuilder $routes) {
    $routes->fallbacks('InflectedRoute');
});
