<?php
use Cake\Routing\Router;

Router::scope('/Workflows', ['plugin' => 'Workflow'], function ($routes) {
	Router::connect('/Workflows', ['plugin' => 'Workflow', 'controller' => 'Workflows']);
	Router::connect('/Workflows/:action/*', ['plugin' => 'Workflow', 'controller' => 'Workflows']);
});

Router::scope('/WorkflowSteps', ['plugin' => 'Workflow'], function ($routes) {
	Router::connect('/WorkflowSteps', ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps']);
	Router::connect('/WorkflowSteps/:action/*', ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps']);
});
