<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::scope('/oauth', ['plugin' => 'OAuth', 'controller' => 'OAuth'], function ($r) {
    $r->extensions(['json']);
    $r->connect('/:action/*', ['_ext' => 'json']);
});

Router::scope('/OAuth', ['plugin' => 'OAuth', 'controller' => 'OAuth'], function ($r) {
    $r->extensions(['json']);
    $r->connect('/:action/*', ['_ext' => 'json']);
});
