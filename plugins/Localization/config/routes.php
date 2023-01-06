<?php
use Cake\Routing\Router;

Router::scope('/Translations', ['plugin' => 'Localization', 'controller' => 'Translations'], function ($routes) {
    $routes->connect('/');
    $routes->connect('/translate/*', ['action' => 'translate', '_ext' => 'json']);
    $routes->connect('/:action/*');
});
