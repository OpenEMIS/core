<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Configurations', ['plugin' => 'Configuration'], function (RouteBuilder$routes) {
	Router::connect('/Configurations', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
	Router::connect('/Configurations/:action/*', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
});
