<?php
use Cake\Routing\Router;

Router::scope('/Outcomes', ['plugin' => 'Outcome'], function ($routes) {
    Router::connect('/Outcomes', ['plugin' => 'Outcome', 'controller' => 'Outcomes']);
    Router::connect('/Outcomes/:action/*', ['plugin' => 'Outcome', 'controller' => 'Outcomes']);
});
