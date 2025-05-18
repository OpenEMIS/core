<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Infrastructures', ['plugin' => 'Infrastructure'], function (RouteBuilder $routes) {
	Router::connect('/Infrastructures', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures']);
	Router::connect('/Infrastructures/:action/*', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures']);
});
