<?php
use Cake\Routing\Router;

Router::plugin('OpenEmis', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
