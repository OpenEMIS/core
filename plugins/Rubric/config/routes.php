<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/', ['plugin' => 'Rubric'], function (RouteBuilder $routes) {
	$routes->connect('/Rubrics', ['plugin' => 'Rubric', 'controller' => 'Rubrics']);
	$routes->connect('/Rubrics/:action/*', ['plugin' => 'Rubric', 'controller' => 'Rubrics']);
});

Router::scope('/RubricStatuses', ['plugin' => 'Rubric'], function (RouteBuilder $routes) {
	$routes->connect('/RubricStatuses', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses']);
	$routes->connect('/RubricStatuses/:action/*', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses']);
});
