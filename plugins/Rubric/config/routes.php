<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/', ['plugin' => 'Rubric'], function (RouteBuilder $routes) {
    	$routes->connect('/Rubrics', ['plugin' => 'Rubric', 'controller' => 'Rubrics']);
    	$routes->connect('/Rubrics/:action/*', ['plugin' => 'Rubric', 'controller' => 'Rubrics']);
    });;

    $routes->scope('/RubricStatuses', ['plugin' => 'Rubric'], function (RouteBuilder $routes) {
    	$routes->connect('/RubricStatuses', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses']);
    	$routes->connect('/RubricStatuses/:action/*', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses']);
    });
};