<?php
use Cake\Routing\Router;

Router::scope('/Map', ['plugin' => 'Map'], function ($routes) {
	Router::connect('/Map', ['plugin' => 'Map', 'controller' => 'Map']);
	Router::connect('/Map/:action/*', ['plugin' => 'Map', 'controller' => 'Map']);
});
