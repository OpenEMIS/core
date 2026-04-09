<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Configurations', ['plugin' => 'Configuration'], function (RouteBuilder $routes) {
    	$routes->connect('/Configurations', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
    	$routes->connect('/Configurations/:action/*', ['plugin' => 'Configuration', 'controller' => 'Configurations']);
    });
    //POCOR-9257: start - standalone Webhooks controller route
    $routes->scope('/Webhooks', ['plugin' => 'Configuration'], function (RouteBuilder $routes) {
        $routes->connect('/Webhooks', ['plugin' => 'Configuration', 'controller' => 'Webhooks', 'action' => 'Webhooks']); //POCOR-9257
        $routes->connect('/Webhooks/:action/*', ['plugin' => 'Configuration', 'controller' => 'Webhooks']); //POCOR-9257
    });
    //POCOR-9257: end
};