<?php
use Cake\Routing\Router;

Router::scope('/Adaptation', ['plugin' => 'Adaptation'], function ($routes) {
    Router::connect('/Adaptation', ['plugin' => 'Adaptation', 'controller' => 'Adaptations']);
    Router::connect('/Adaptation/:action/*', ['plugin' => 'Adaptation', 'controller' => 'Adaptations']);
});
