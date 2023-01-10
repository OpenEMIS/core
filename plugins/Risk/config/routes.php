<?php
use Cake\Routing\Router;

Router::scope('/Risk', ['plugin' => 'Risk'], function ($routes) {
    Router::connect('/Risks', ['plugin' => 'Risk', 'controller' => 'Risks']);
    Router::connect('/Risks/:action/*', ['plugin' => 'Risk', 'controller' => 'Risks']);
});
