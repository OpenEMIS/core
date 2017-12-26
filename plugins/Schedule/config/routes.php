<?php
use Cake\Routing\Router;

Router::scope('/Schedules', ['plugin' => 'Schedule'], function ($routes) {
    $routes->connect('/', ['controller' => 'Schedules']);
    $routes->connect('/:action/*', ['controller' => 'Schedules']);
});
