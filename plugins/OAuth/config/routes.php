<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/oauth', ['controller' => 'OAuth'], function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);
        $builder->connect('/:action/*', ['_ext' => 'json']);
    });
    
    // $routes->scope('/oauth', ['controller' => 'OAuth'], function ($r) {
    //     $r->extensions(['json']);
    //     $r->connect('/:action/*', ['_ext' => 'json']);
    // })
};