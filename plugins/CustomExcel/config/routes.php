<?php
use Cake\Routing\Router;

Router::scope('/CustomExcels', ['plugin' => 'CustomExcel'], function ($routes) {
    Router::connect('/CustomExcels', ['plugin' => 'CustomExcel', 'controller' => 'CustomExcels']);
    Router::connect('/CustomExcels/:action/*', ['plugin' => 'CustomExcel', 'controller' => 'CustomExcels']);
});
