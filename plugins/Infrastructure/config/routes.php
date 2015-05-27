<?php
use Cake\Routing\Router;

Router::scope('/Infrastructures', ['plugin' => 'Infrastructure'], function ($routes) {
	Router::connect('/Infrastructures', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures']);
	Router::connect('/Infrastructures/:action/*', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures']);
});
