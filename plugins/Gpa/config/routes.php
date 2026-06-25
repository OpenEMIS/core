<?php
use Cake\Routing\RouteBuilder;
return function (RouteBuilder $routes) {
    $routes->scope('/Gpa', ['plugin' => 'Gpa'], function (RouteBuilder $routes) {
        $routes->connect('/Gpa', ['plugin' => 'Gpa', 'controller' => 'Gpa', 'action' => 'index']);
        $routes->connect('/Gpa/:action/*', ['plugin' => 'Gpa', 'controller' => 'Gpa']);
    });;
    
    
    /*$routes->scope('/Gpa', ['plugin' => 'Gpa'], function (RouteBuilder $routes) {
        // Route to the index action of the Gpa controller
        $routes->connect('/Gpa', ['controller' => 'Gpa', 'action' => 'index']);
        
        // Route to other actions of the Gpa controller
        $routes->connect('/Gpa/:action/*', ['controller' => 'Gpa']);
    });;*/
};
