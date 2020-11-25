<?php
use Cake\Routing\Router;

Router::scope('/ProfileTemplate', ['plugin' => 'ProfileTemplate'], function ($routes) {
    Router::connect('/ProfileTemplate', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplate']);
    Router::connect('/ProfileTemplate/:action/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplate']);
});
