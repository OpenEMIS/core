<?php
use Cake\Routing\Router;

Router::scope('/Assessments', ['plugin' => 'Assessment'], function ($routes) {
	Router::connect('/Assessments', ['plugin' => 'Assessment', 'controller' => 'Assessments']);
	Router::connect('/Assessments/:action/*', ['plugin' => 'Assessment', 'controller' => 'Assessments']);
});
