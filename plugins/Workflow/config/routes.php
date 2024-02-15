<?php
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;

Router::scope('/', ['plugin' => 'Workflow'], function (RouteBuilder $routes) {
	$routes->connect('/Workflows', ['plugin' => 'Workflow', 'controller' => 'Workflows']);
	$routes->connect('/Workflows/:action/*', ['plugin' => 'Workflow', 'controller' => 'Workflows']);
});

Router::scope('/WorkflowSteps', ['plugin' => 'Workflow'], function (RouteBuilder $routes) {
	$routes->connect('/WorkflowSteps', ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps']);
	$routes->connect('/WorkflowSteps/:action/*', ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps']);
});
