<?php
use Cake\Routing\Router;

Router::scope('/Maps', ['plugin' => 'Map'], function ($routes) {
	Router::connect('/Maps', ['plugin' => 'Map', 'controller' => 'Maps']);
	Router::connect('/Maps/:action/*', ['plugin' => 'Map', 'controller' => 'Maps']);
});
