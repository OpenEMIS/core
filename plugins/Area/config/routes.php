<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Areas', ['plugin' => 'Area'], function (RouteBuilder $routes) {
	Router::connect('/Areas', ['plugin' => 'Area', 'controller' => 'Areas']);
	Router::connect('/Areas/:action/*', ['plugin' => 'Area', 'controller' => 'Areas']);
});
