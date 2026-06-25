<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Installer', ['plugin' => 'Installer'], function (RouteBuilder $routes) {
        $routes->connect('/Installer', ['plugin' => 'Installer', 'controller' => 'Installer']);
        $routes->connect('/Installer/:action/*', ['plugin' => 'Installer', 'controller' => 'Installer']);
    });
};