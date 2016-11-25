<?php
use Cake\Routing\Router;

Router::scope('/CustomReports', ['plugin' => 'CustomReport'], function ($routes) {
    Router::connect('/CustomReports', ['plugin' => 'CustomReport', 'controller' => 'CustomReports']);
    Router::connect('/CustomReports/:action/*', ['plugin' => 'CustomReport', 'controller' => 'CustomReports']);
});
