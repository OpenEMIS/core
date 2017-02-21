<?php
use Cake\Routing\Router;

Router::scope('/Indexes', ['plugin' => 'Indexes'], function ($routes) {
    Router::connect('/Indexes', ['plugin' => 'Indexes', 'controller' => 'Indexes']);
    Router::connect('/Indexes/:action/*', ['plugin' => 'Indexes', 'controller' => 'Indexes']);
});
