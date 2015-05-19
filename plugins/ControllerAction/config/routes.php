<?php
use Cake\Routing\Router;

Router::plugin('ControllerAction', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
