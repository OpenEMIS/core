<?php
use Cake\Routing\Router;

Router::scope('/Manuals', ['plugin' => 'Manual'], function ($routes) {
    Router::connect('/Manuals', ['plugin' => 'Manual', 'controller' => 'Manuals']);
    Router::connect('/Manuals/:action/*', ['plugin' => 'Manual', 'controller' => 'Manuals']);
});
