<?php
use Cake\Routing\RouteBuilder;
return function (RouteBuilder $routes) {
    $routes->scope('/FieldOptions', ['plugin' => 'FieldOption'], function (RouteBuilder $routes) {
    	$routes->connect('/FieldOptions', ['plugin' => 'FieldOption', 'controller' => 'FieldOptions']);
    	$routes->connect('/FieldOptions/:action/*', ['plugin' => 'FieldOption', 'controller' => 'FieldOptions']);
    });
};
