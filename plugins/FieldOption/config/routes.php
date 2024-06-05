<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/FieldOptions', ['plugin' => 'FieldOption'], function (RouteBuilder $routes) {
	$routes->connect('/FieldOptions', ['plugin' => 'FieldOption', 'controller' => 'FieldOptions']);
	$routes->connect('/FieldOptions/:action/*', ['plugin' => 'FieldOption', 'controller' => 'FieldOptions']);
});
