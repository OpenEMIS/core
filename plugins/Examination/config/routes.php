<?php
use Cake\Routing\Router;

Router::scope('/Examinations', ['plugin' => 'Examination'], function ($routes) {
    Router::connect('/Examinations', ['plugin' => 'Examination', 'controller' => 'Examinations']);
    Router::connect('/Examinations/:action/*', ['plugin' => 'Examination', 'controller' => 'Examinations']);
});
