<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

$routes->scope('/InstitutionCustomFields', ['plugin' => 'InstitutionCustomField'], function (RouteBuilder $routes) {
	Router::connect('/InstitutionCustomFields', ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields']);
	Router::connect('/InstitutionCustomFields/:action/*', ['plugin' => 'InstitutionCustomField', 'controller' => 'InstitutionCustomFields']);
});
