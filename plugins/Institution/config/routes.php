<?php
use Cake\Core\Plugin;
use Cake\Routing\Router;

Router::plugin('Institution', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
