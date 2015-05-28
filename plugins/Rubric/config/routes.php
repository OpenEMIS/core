<?php
use Cake\Routing\Router;

Router::scope('/Rubrics', ['plugin' => 'Rubric'], function ($routes) {
	Router::connect('/Rubrics', ['plugin' => 'Rubric', 'controller' => 'Rubrics']);
	Router::connect('/Rubrics/:action/*', ['plugin' => 'Rubric', 'controller' => 'Rubrics']);
});

Router::scope('/RubricStatuses', ['plugin' => 'Rubric'], function ($routes) {
	Router::connect('/RubricStatuses', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses']);
	Router::connect('/RubricStatuses/:action/*', ['plugin' => 'Rubric', 'controller' => 'RubricStatuses']);
});
