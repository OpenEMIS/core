<?php
use Cake\Routing\Router;

Router::scope('/Outcomes', ['plugin' => 'Outcome', 'controller' => 'Outcomes'], function ($routes) {
    $routes->connect('/:action/*');
});
