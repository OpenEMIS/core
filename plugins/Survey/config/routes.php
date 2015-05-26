<?php
use Cake\Routing\Router;

Router::scope('/survey_templates', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/survey_templates', ['plugin' => 'Survey', 'controller' => 'SurveyTemplates']);
	Router::connect('/survey_templates/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyTemplates']);
	$routes->fallbacks('InflectedRoute');
});

Router::scope('/survey_questions', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/survey_questions', ['plugin' => 'Survey', 'controller' => 'SurveyQuestions']);
	Router::connect('/survey_questions/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyQuestions']);
	$routes->fallbacks('InflectedRoute');
});

Router::scope('/survey_statuses', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/survey_statuses', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
	Router::connect('/survey_statuses/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
	$routes->fallbacks('InflectedRoute');
});
