<?php
use Cake\Routing\Router;

Router::scope('/ReportCards', ['plugin' => 'ReportCard'], function ($routes) {
    Router::connect('/ReportCards', ['plugin' => 'ReportCard', 'controller' => 'ReportCards']);
    Router::connect('/ReportCards/:action/*', ['plugin' => 'ReportCard', 'controller' => 'ReportCards']);
});
