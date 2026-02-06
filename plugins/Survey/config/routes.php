<?php
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes) {
    $routes->scope('/Surveys', ['plugin' => 'Survey'], function (RouteBuilder $routes) {
    	$routes->connect('/Surveys', ['plugin' => 'Survey', 'controller' => 'Surveys']);
    	$routes->connect('/Surveys/:action/*', ['plugin' => 'Survey', 'controller' => 'Surveys']);
    });;

    $routes->scope('/SurveyStatuses', ['plugin' => 'Survey'], function (RouteBuilder $routes) {
    	$routes->connect('/SurveyStatuses', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
    	$routes->connect('/SurveyStatuses/:action/*', ['plugin' => 'Survey', 'controller' => 'SurveyStatuses']);
    });
};