<?php
use Cake\Routing\Router;

Router::plugin('Localization', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
