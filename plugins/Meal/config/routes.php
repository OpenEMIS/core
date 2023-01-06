<?php
use Cake\Routing\Router;

Router::scope('/Meals', ['plugin' => 'Meal'], function ($routes) {
    Router::connect('/Meals', ['plugin' => 'Meal', 'controller' => 'Meals']);
    Router::connect('/Meals/:action/*', ['plugin' => 'Meal', 'controller' => 'Meals']);
});