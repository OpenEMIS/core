<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/CustomExcels', ['plugin' => 'CustomExcel'], function (RouteBuilder $routes) {
        $routes->connect('/CustomExcels', ['plugin' => 'CustomExcel', 'controller' => 'CustomExcels']);
        $routes->connect('/CustomExcels/:action/*', ['plugin' => 'CustomExcel', 'controller' => 'CustomExcels']);
    });
};