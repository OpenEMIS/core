<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/Gpa', ['plugin' => 'Gpa'], function (RouteBuilder $routes) {
    $routes->connect('/Gpa', ['plugin' => 'Gpa', 'controller' => 'Gpa', 'action' => 'index']);
    $routes->connect('/Gpa/:action/*', ['plugin' => 'Gpa', 'controller' => 'Gpa']);
});


/*Router::scope('/Gpa', ['plugin' => 'Gpa'], function (RouteBuilder $routes) {
    // Route to the index action of the Gpa controller
    $routes->connect('/Gpa', ['controller' => 'Gpa', 'action' => 'index']);
    
    // Route to other actions of the Gpa controller
    $routes->connect('/Gpa/:action/*', ['controller' => 'Gpa']);
});*/
