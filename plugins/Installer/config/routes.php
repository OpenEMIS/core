<?php
use Cake\Routing\Router;

Router::scope('/Installer', ['plugin' => 'Installer'], function ($routes) {
    Router::connect('/Installer', ['plugin' => 'Installer', 'controller' => 'Installer']);
    Router::connect('/Installer/:action/*', ['plugin' => 'Installer', 'controller' => 'Installer']);
});
