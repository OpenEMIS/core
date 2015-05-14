<?php
Router::connect('/SurveyTemplates', array('plugin' => 'Surveys', 'controller' => 'SurveyTemplates', 'action' => 'index'));
Router::connect('/SurveyTemplates/:action/*', array('controller' => 'SurveyTemplates', 'plugin' => 'Surveys'));

Router::connect('/SurveyStatuses', array('plugin' => 'Surveys', 'controller' => 'SurveyStatuses', 'action' => 'index'));
Router::connect('/SurveyStatuses/:action/*', array('controller' => 'SurveyStatuses', 'plugin' => 'Surveys'));

Router::connect('/SurveyQuestions', array('plugin' => 'Surveys', 'controller' => 'SurveyQuestions', 'action' => 'index'));
Router::connect('/SurveyQuestions/:action/*', array('controller' => 'SurveyQuestions', 'plugin' => 'Surveys'));

Router::connect('/SurveyReports/Security/login', array('controller' => 'Security', 'action' => 'login'));
Router::connect('/SurveyReports', array('plugin' => 'Surveys', 'controller' => 'SurveyReports'));
Router::connect('/SurveyReports/:action/*', array('plugin' => 'Surveys', 'controller' => 'SurveyReports'));
