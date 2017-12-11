<?php
use Cake\Routing\Router;

Router::scope('/Theme', ['plugin' => 'Theme'], function ($routes) {
    Router::connect('/Theme', ['plugin' => 'Theme', 'controller' => 'Themes']);
    Router::connect('/Theme/:action/*', ['plugin' => 'Theme', 'controller' => 'Themes']);
});
