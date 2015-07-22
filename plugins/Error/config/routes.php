<?php
use Cake\Routing\Router;

Router::scope('/Error', ['plugin' => 'Error'], function ($routes) {
	Router::connect('/Errors', ['plugin' => 'Error', 'controller' => 'Errors']);
	Router::connect('/Errors/:action/*', ['plugin' => 'Error', 'controller' => 'Errors']);
});
