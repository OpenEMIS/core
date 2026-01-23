<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->fallbacks('InflectedRoute');
};
