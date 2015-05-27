<?php
use Cake\Routing\Router;

Router::plugin('CustomField', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
