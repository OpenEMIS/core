<?php
use Cake\Routing\Router;

Router::scope('/ProfileTemplates', ['plugin' => 'ProfileTemplate'], function ($routes) {
    Router::connect('/ProfileTemplates', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates']);
    Router::connect('/ProfileTemplates/:action/*', ['plugin' => 'ProfileTemplate', 'controller' => 'ProfileTemplates']);
});
