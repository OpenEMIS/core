<?php
use Cake\Routing\Router;

Router::scope('/MoodleApi', ['plugin' => 'MoodleApi'], function ($routes) {
    $routes->connect('/', ['plugin' => 'MoodleApi', 'controller' => 'MoodleApi']);
});
