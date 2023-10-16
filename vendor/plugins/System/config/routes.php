<?php
use Cake\Routing\Router;

Router::scope('/Systems', ['plugin' => 'System'], function ($routes) {
	Router::connect('/Systems', ['plugin' => 'System', 'controller' => 'Systems']);
	Router::connect('/Systems/:action/*', ['plugin' => 'System', 'controller' => 'Systems']);
});
