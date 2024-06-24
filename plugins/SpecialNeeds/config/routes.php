<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/SpecialNeeds', ['plugin' => 'SpecialNeeds'], function (RouteBuilder $routes) {
	$routes->connect('/SpecialNeeds', ['plugin' => 'SpecialNeeds', 'controller' => 'SpecialNeeds']);
	$routes->connect('/SpecialNeeds/:action/*', ['plugin' => 'SpecialNeeds', 'controller' => 'SpecialNeeds']);
});
