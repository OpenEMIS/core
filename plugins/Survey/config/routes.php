<?php
use Cake\Routing\Router;

Router::scope('/SurveyTemplates', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/SurveyTemplates', ['plugin' => 'Survey', 'controller' => 'SurveyTemplates']);
	Router::connect('/SurveyTemplates/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyTemplates']);
});

Router::scope('/SurveyQuestions', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/SurveyQuestions', ['plugin' => 'Survey', 'controller' => 'SurveyQuestions']);
	Router::connect('/SurveyQuestions/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyQuestions']);
});

Router::scope('/SurveyStatuses', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/SurveyStatuses', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
	Router::connect('/SurveyStatuses/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
});
