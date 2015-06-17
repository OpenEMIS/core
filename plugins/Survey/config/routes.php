<?php
use Cake\Routing\Router;

Router::scope('/Surveys', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/Surveys', ['plugin' => 'Survey', 'controller' => 'Surveys']);
	Router::connect('/Surveys/:action/*', ['plugin' => 'Survey', 'controller' => 'Surveys']);
});

Router::scope('/SurveyStatuses', ['plugin' => 'Survey'], function ($routes) {
	Router::connect('/SurveyStatuses', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
	Router::connect('/SurveyStatuses/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
});
