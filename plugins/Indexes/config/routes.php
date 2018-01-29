<?php
use Cake\Routing\Router;

Router::scope('/Risk', ['plugin' => 'Indexes'], function ($routes) {
    Router::connect('/Risks', ['plugin' => 'Indexes', 'controller' => 'Risks']);
    Router::connect('/Risks/:action/*', ['plugin' => 'Indexes', 'controller' => 'Risks']);
});
