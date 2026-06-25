<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Alerts', ['plugin' => 'Alert'], function (RouteBuilder $routes) {
    	$routes->connect('/Alerts', ['plugin' => 'Alert', 'controller' => 'Alerts']);
    	$routes->connect('/Alerts/:action/*', ['plugin' => 'Alert', 'controller' => 'Alerts']);
    });

    //POCOR-9257: Route /Webhook/* to Alert plugin WebhookController
    $routes->scope('/Webhook', ['plugin' => 'Alert'], function (RouteBuilder $routes) {
        $routes->connect('/', ['plugin' => 'Alert', 'controller' => 'Webhook', 'action' => 'WebhookQueue']);
        $routes->connect('/:action/*', ['plugin' => 'Alert', 'controller' => 'Webhook']);
    });
};
