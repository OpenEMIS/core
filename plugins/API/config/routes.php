<?php
use Cake\Routing\Router;

Router::scope('/api', ['plugin' => 'API'], function ($routes) {
	Router::connect('/api', ['plugin' => 'API', 'controller' => 'Api']);
	Router::connect('/api/:action/*', ['plugin' => 'API', 'controller' => 'Api']);
});
