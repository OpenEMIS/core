<?php

use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->scope('/oauth', ['controller' => 'OAuth'], function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);
        $builder->connect('/:action/*', ['_ext' => 'json']);
    });
};

// Router::scope('/oauth', ['controller' => 'OAuth'], function ($r) {
//     $r->extensions(['json']);
//     $r->connect('/:action/*', ['_ext' => 'json']);
// });
